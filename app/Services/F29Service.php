<?php

namespace App\Services;

use App\SiiResumenMensual;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * F29Service
 *
 * IVA a pagar del F29 con arrastre del remanente de credito fiscal:
 *
 *   IVA a pagar (cod. 089) = Debito - Credito - Remanente mes anterior reajustado (cod. 504)
 *   Si es negativo, ese monto es el remanente para el periodo siguiente (cod. 077).
 *
 * Reajuste (art. 27 DL 825): el remanente se convierte a UTM con la UTM del mes
 * en que se genero y se reconvierte con la UTM del mes en que se imputa.
 * La UTM oficial se obtiene de mindicador.cl y queda en cache.
 *
 * La cadena parte en config('sii.remanente_inicial_periodo') con el remanente
 * de config('sii.remanente_inicial') (cod. 077 del F29 del mes anterior). Si
 * ese valor no esta informado se asume 0 y se marca como supuesto. Si un mes
 * de la cadena no esta sincronizado, desde ahi el remanente es desconocido y
 * se marca cadena_incompleta.
 *
 * Compatible Laravel 6 / PHP 7.2.
 */
class F29Service
{
    /** @var array cache en memoria de la cadena ya calculada, por periodo final */
    private $cadenas = [];

    /**
     * Datos de IVA del F29 para un periodo (YYYYMM). Null si el periodo no
     * esta sincronizado o es anterior al inicio de la cadena.
     */
    public function ivaPeriodo($periodo)
    {
        $cadena = $this->cadena($periodo);
        return isset($cadena[$periodo]) ? $cadena[$periodo] : null;
    }

    /**
     * Cadena de IVA desde el periodo inicial hasta $hasta (YYYYMM), inclusive.
     *
     * @return array [periodo => [debito, credito, utm, remanente_anterior,
     *               iva_a_pagar, remanente_siguiente, sincronizado,
     *               cadena_incompleta, remanente_inicial_supuesto, sin_utm]]
     */
    public function cadena($hasta)
    {
        if (isset($this->cadenas[$hasta])) {
            return $this->cadenas[$hasta];
        }

        $inicio = (string) config('sii.remanente_inicial_periodo', '202601');
        if ($hasta < $inicio) {
            return $this->cadenas[$hasta] = [];
        }

        $inicial   = config('sii.remanente_inicial');
        $supuesto  = $inicial === null || $inicial === '';
        $remanente = $supuesto ? 0 : (int) $inicial; // pesos, a UTM del mes previo
        $conocido  = true;

        $filas = SiiResumenMensual::whereBetween('periodo', [$inicio, $hasta])
            ->get()
            ->keyBy('periodo');

        $mes     = Carbon::createFromFormat('Ym d', $inicio . ' 01')->startOfMonth();
        $utmPrev = $this->utm($mes->copy()->subMonthNoOverflow()->format('Ym'));
        $cadena  = [];

        while ($mes->format('Ym') <= $hasta) {
            $p   = $mes->format('Ym');
            $r   = isset($filas[$p]) ? $filas[$p] : null;
            $utm = $this->utm($p);

            if (!$r) {
                // Mes sin sincronizar: no se sabe cuanto remanente deja.
                $cadena[$p] = null;
                $conocido   = false;
                $utmPrev    = $utm;
                $mes->addMonthNoOverflow();
                continue;
            }

            $debito  = (int) $r->iva_debito;
            $credito = (int) $r->iva_credito;
            $sinUtm  = false;

            if (!$conocido) {
                $remanenteAnterior = null;
            } elseif ($remanente === 0) {
                $remanenteAnterior = 0;
            } elseif ($utmPrev && $utm) {
                $remanenteAnterior = (int) round($remanente / $utmPrev * $utm);
            } else {
                $remanenteAnterior = $remanente; // sin UTM: nominal
                $sinUtm = true;
            }

            $neto = $debito - $credito - (int) $remanenteAnterior;

            $cadena[$p] = [
                'debito'                     => $debito,
                'credito'                    => $credito,
                'utm'                        => $utm,
                'remanente_anterior'         => $remanenteAnterior,
                'iva_a_pagar'                => $neto > 0 ? $neto : 0,
                'remanente_siguiente'        => $neto < 0 ? -$neto : 0,
                'sincronizado'               => true,
                'cadena_incompleta'          => !$conocido,
                'remanente_inicial_supuesto' => $supuesto,
                'sin_utm'                    => $sinUtm,
            ];

            $remanente = $neto < 0 ? -$neto : 0;
            $utmPrev   = $utm;
            $mes->addMonthNoOverflow();
        }

        return $this->cadenas[$hasta] = $cadena;
    }

    /**
     * UTM oficial del mes (YYYYMM), en pesos. Null si no se pudo obtener.
     */
    public function utm($periodo)
    {
        $key   = 'utm_' . $periodo;
        $valor = Cache::get($key);
        if ($valor) {
            return (int) $valor;
        }

        $anio = substr($periodo, 0, 4);
        if (Cache::has('utm_fallo_' . $anio)) {
            return null; // fallo reciente: no reintentar en cada request
        }
        try {
            $ctx  = stream_context_create(['http' => ['timeout' => 8]]);
            $json = @file_get_contents('https://mindicador.cl/api/utm/' . $anio, false, $ctx);
            $data = $json ? json_decode($json, true) : null;
            if (!empty($data['serie'])) {
                foreach ($data['serie'] as $s) {
                    $p = str_replace('-', '', substr($s['fecha'], 0, 7));
                    if ((int) $s['valor'] > 0) {
                        Cache::forever('utm_' . $p, (int) $s['valor']);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('F29Service: no se pudo obtener UTM', ['periodo' => $periodo, 'error' => $e->getMessage()]);
        }

        $valor = Cache::get($key);
        if (!$valor) {
            Cache::put('utm_fallo_' . $anio, 1, 3600);
            return null;
        }
        return (int) $valor;
    }
}
