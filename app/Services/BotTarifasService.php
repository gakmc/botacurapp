<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * BotTarifasService
 *
 * Única fuente de verdad para los montos y reglas comerciales del bot.
 * La IA NUNCA calcula montos: pide una cotización (accion "cotizar") y
 * repite lo que devuelve este servicio. BotReservaController usa los mismos
 * cálculos al crear holds, reservas y cobros Webpay.
 *
 * Los parámetros se editan en Configuración del bot → pestaña "Parámetros"
 * (tabla bot_secciones_texto, categoria = 'parametros'). Si un valor está
 * vacío o es inválido se usa el valor por defecto de parametrosDefault().
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotTarifasService
{
    const TZ = 'America/Santiago';

    /** clave => [etiqueta, valor por defecto] */
    public static function parametrosDefault(): array
    {
        return [
            'param_programas_bot'           => ['Programas que el bot puede ofrecer y reservar (IDs separados por coma)', '1,28,39,48,3,43,45,49'],
            'param_programas_semana'        => ['Programas de semana "S" (IDs)', '3,43,45,49'],
            'param_meses_semana'            => ['Meses en que se venden los programas S (números 1-12)', '3,4,5,6,7,8,9,10'],
            'param_recargo_verano'          => ['Recargo de temporada de verano por persona ($)', '5000'],
            'param_meses_verano'            => ['Meses de temporada de verano (números 1-12)', '11,12,1,2'],
            'param_programas_sin_recargo'   => ['Programas sin recargo de verano (IDs)', '20,21,22'],
            'param_programas_pago_total'    => ['Programas que se pagan 100% y solo por transferencia, ej. Extendidos o Cyber (IDs)', ''],
            'param_abono_transferencia_pct' => ['Abono al reservar por transferencia (%)', '50'],
            'param_pago_tarjeta_pct'        => ['Pago al reservar con tarjeta / Webpay (%)', '100'],
            'param_grupo_max_bot'           => ['Máximo de personas que el bot puede reservar (más que esto se deriva a correo)', '9'],
            'param_dia_apertura_agenda'     => ['Día del mes en que se abre la agenda del mes siguiente', '20'],
            'param_feriados'                => ['Feriados de jueves o viernes (una fecha AAAA-MM-DD por línea). Los programas S no aplican esos días', ''],
            'param_horas_silencio_humano'   => ['Horas que el bot queda en silencio en un chat cuando responde una persona o se deriva', '12'],
        ];
    }

    private static $cache = null;

    public static function parametros(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $def = self::parametrosDefault();
        $bd  = [];
        try {
            $bd = DB::table('bot_secciones_texto')->whereIn('clave', array_keys($def))->pluck('contenido', 'clave')->all();
        } catch (\Throwable $e) {
            $bd = [];
        }
        $out = [];
        foreach ($def as $k => $d) {
            $v = isset($bd[$k]) ? trim((string) $bd[$k]) : '';
            $out[$k] = ($v === '' && $k !== 'param_feriados' && $k !== 'param_programas_pago_total') ? $d[1] : $v;
        }
        return self::$cache = $out;
    }

    public static function limpiarCache()
    {
        self::$cache = null;
    }

    public static function lista($texto): array
    {
        $out = [];
        foreach (preg_split('/[\s,;]+/', (string) $texto) as $p) {
            if ($p !== '' && ctype_digit($p)) {
                $out[] = (int) $p;
            }
        }
        return array_values(array_unique($out));
    }

    private static function entero(string $clave): int
    {
        $p = self::parametros();
        $v = preg_replace('/[^0-9]/', '', (string) ($p[$clave] ?? ''));
        if ($v === '') {
            $v = preg_replace('/[^0-9]/', '', self::parametrosDefault()[$clave][1]);
        }
        return (int) $v;
    }

    private static function pct(string $clave): int
    {
        $v = self::entero($clave);
        return ($v < 1 || $v > 100) ? (int) self::parametrosDefault()[$clave][1] : $v;
    }

    public static function idsProgramasBot(): array    { return self::lista(self::parametros()['param_programas_bot']); }
    public static function idsProgramasSemana(): array { return self::lista(self::parametros()['param_programas_semana']); }
    public static function horasSilencioHumano(): int  { return max(1, self::entero('param_horas_silencio_humano')); }
    public static function grupoMaximo(): int          { return max(1, self::entero('param_grupo_max_bot')); }

    public static function feriados(): array
    {
        preg_match_all('/\d{4}-\d{2}-\d{2}/', (string) self::parametros()['param_feriados'], $m);
        return $m[0];
    }

    /** Precios unitarios de extras, leídos de la BD (mismo origen que BotReservaController). */
    public static function preciosExtras(): array
    {
        $masaje = null;
        $dyo    = null;
        try {
            $pm = DB::table('precios_tipos_masajes')->where('id', 1)->first();
            if ($pm) {
                $masaje = (int) $pm->precio_unitario;
            } else {
                $sm = DB::table('servicios')->where('slug', 'masaje')->first();
                $masaje = $sm ? (int) $sm->valor_servicio : null;
            }
            $sd  = DB::table('servicios')->where('slug', 'desayuno-u-once')->first();
            $dyo = $sd ? (int) $sd->valor_servicio : null;
        } catch (\Throwable $e) {
        }
        return [
            'masaje'        => $masaje ?: 25000,
            'desayuno_once' => $dyo ?: 10000,
        ];
    }

    public static function fmt($n): string
    {
        return '$' . number_format((int) $n, 0, ',', '.');
    }

    public static function esTransferencia($tipoPago): bool
    {
        return strpos(mb_strtolower((string) $tipoPago), 'transf') !== false;
    }

    public static function esTarjeta($tipoPago): bool
    {
        $t = mb_strtolower((string) $tipoPago);
        return $t !== '' && (strpos($t, 'd') === 0 || strpos($t, 'cr') === 0 || strpos($t, 'tarj') !== false || strpos($t, 'webpay') !== false);
    }

    /** ¿La agenda del mes de $fecha ya está abierta? Devuelve [bool, fecha_apertura|null]. */
    public static function agendaAbierta(Carbon $fecha): array
    {
        $hoy   = Carbon::now(self::TZ)->startOfDay();
        $mesF  = $fecha->year * 12 + $fecha->month;
        $mesH  = $hoy->year * 12 + $hoy->month;
        $dia   = min(28, max(1, self::entero('param_dia_apertura_agenda')));
        if ($mesF <= $mesH) {
            return [true, null];
        }
        $apertura = $fecha->copy()->startOfMonth()->subMonth()->day($dia);
        if ($mesF === $mesH + 1 && $hoy->greaterThanOrEqualTo($apertura)) {
            return [true, null];
        }
        return [false, $apertura];
    }

    /**
     * Cotiza y valida. $d: programa_id, fecha (Y-m-d), personas, masajes_extra, desayuno_once, tipo_pago.
     * $validar=false solo calcula montos (lo usa BotReservaController sobre datos ya validados).
     */
    public static function cotizar(array $d, bool $validar = true): array
    {
        $errores = [];
        $notas   = [];
        $err = function ($codigo, $mensaje) use (&$errores) {
            $errores[] = ['codigo' => $codigo, 'mensaje' => $mensaje];
        };

        $p          = self::parametros();
        $programaId = (int) ($d['programa_id'] ?? 0);
        $personas   = (int) ($d['personas'] ?? 0);
        $masajes    = max(0, (int) ($d['masajes_extra'] ?? 0));
        $dyo        = max(0, (int) ($d['desayuno_once'] ?? 0));
        $tipoPago   = trim((string) ($d['tipo_pago'] ?? ''));
        $fechaTxt   = trim((string) ($d['fecha'] ?? ''));

        $programa = $programaId ? DB::table('programas')->where('id', $programaId)->first() : null;
        $fecha    = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaTxt)) {
            try {
                $fecha = Carbon::createFromFormat('Y-m-d', $fechaTxt, self::TZ)->startOfDay();
            } catch (\Throwable $e) {
                $fecha = null;
            }
        }

        $esSemana    = in_array($programaId, self::idsProgramasSemana(), true);
        $esPagoTotal = in_array($programaId, self::lista($p['param_programas_pago_total']), true);

        if ($validar) {
            if (!$programa || !in_array($programaId, self::idsProgramasBot(), true)) {
                $err('programa_no_disponible', 'Ese programa no está disponible para reservar por este canal.');
            }
            if ($personas < 1) {
                $err('personas_invalidas', 'Falta la cantidad de personas.');
            } elseif ($personas > self::grupoMaximo()) {
                $err('grupo_grande', 'Para grupos de ' . (self::grupoMaximo() + 1) . ' o más personas la cotización se hace por correo a hola@botacura.cl (nombre de la empresa u organización, fecha tentativa, cantidad de personas, programa de interés y si necesita factura).');
            } elseif ($programa && !empty($programa->min_personas) && $personas < (int) $programa->min_personas) {
                $err('minimo_personas', 'Este programa es para mínimo ' . (int) $programa->min_personas . ' personas.');
            }
            if (!$fecha) {
                $err('fecha_invalida', 'Falta la fecha de la visita en formato AAAA-MM-DD.');
            } else {
                $hoy = Carbon::now(self::TZ)->startOfDay();
                if ($fecha->lessThan($hoy)) {
                    $err('fecha_pasada', 'Esa fecha ya pasó.');
                } else {
                    list($abierta, $apertura) = self::agendaAbierta($fecha);
                    if (!$abierta) {
                        $err('agenda_no_abierta', 'La agenda de ' . $fecha->locale('es')->isoFormat('MMMM') . ' aún no está abierta; se abre el ' . $apertura->locale('es')->isoFormat('dddd D [de] MMMM') . '.');
                    } else {
                        $habilitada = false;
                        try {
                            $habilitada = \App\FechaDisponible::where('fecha', $fecha->toDateString())->where('habilitada', true)->exists();
                        } catch (\Throwable $e) {
                        }
                        if (!$habilitada) {
                            $err('fecha_no_habilitada', 'Ese día no está habilitado para reservas.');
                        }
                    }
                }
                if ($esSemana) {
                    $dow = $fecha->dayOfWeekIso; // 1=lunes … 7=domingo
                    if ($dow > 5) {
                        $err('semana_solo_lunes_viernes', 'Los programas de semana (S) son solo de lunes a viernes.');
                    } elseif ($dow <= 3) {
                        // Botacura abre lunes a miércoles solo en festivos: no aplica el programa S.
                        $err('semana_feriado', 'Los programas de semana (S) no aplican en feriados.');
                    } elseif (in_array($fecha->toDateString(), self::feriados(), true)) {
                        $err('semana_feriado', 'Los programas de semana (S) no aplican en feriados.');
                    }
                    if (!in_array($fecha->month, self::lista($p['param_meses_semana']), true)) {
                        $err('semana_fuera_de_temporada', 'Los programas de semana (S) no están disponibles en esa fecha (temporada de verano: valor aún no definido).');
                    }
                }
            }
            if (($esSemana || $esPagoTotal) && $tipoPago !== '' && !self::esTransferencia($tipoPago)) {
                $err('solo_transferencia', 'Este programa se paga solo por transferencia.');
            }
        }

        $extras        = self::preciosExtras();
        $valorLista    = $programa ? (int) $programa->valor_programa : 0;
        $recargo       = 0;
        if ($fecha && !$esSemana
            && in_array($fecha->month, self::lista($p['param_meses_verano']), true)
            && !in_array($programaId, self::lista($p['param_programas_sin_recargo']), true)) {
            $recargo = self::entero('param_recargo_verano');
            $notas[] = 'Incluye recargo de temporada de verano de ' . self::fmt($recargo) . ' por persona.';
        }
        $valorPersona  = $valorLista + $recargo;
        $totalPrograma = $valorPersona * max(0, $personas);
        $totalMasajes  = $masajes * $extras['masaje'];
        $totalDyo      = $dyo * $extras['desayuno_once'];
        $total         = $totalPrograma + $totalMasajes + $totalDyo;

        if ($esPagoTotal) {
            $pct = 100;
        } elseif (self::esTransferencia($tipoPago)) {
            $pct = self::pct('param_abono_transferencia_pct');
        } elseif (self::esTarjeta($tipoPago)) {
            $pct = self::pct('param_pago_tarjeta_pct');
        } else {
            $pct = self::pct('param_abono_transferencia_pct');
            $notas[] = 'Medio de pago aún no definido: por transferencia se abona ' . $pct . '% al reservar; con tarjeta (Webpay) se paga ' . self::pct('param_pago_tarjeta_pct') . '% al reservar.';
        }
        $pagoAhora = (int) ceil($total * $pct / 100);
        $saldo     = $total - $pagoAhora;
        if ($esSemana) {
            $notas[] = 'Programa de semana: el precio ya incluye el descuento; no es reagendable.';
        }

        return [
            'ok'                         => empty($errores),
            'errores'                    => $errores,
            'programa_id'                => $programaId,
            'programa'                   => $programa ? $programa->nombre_programa : null,
            'fecha'                      => $fecha ? $fecha->toDateString() : $fechaTxt,
            'fecha_texto'                => $fecha ? $fecha->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') : null,
            'personas'                   => $personas,
            'es_programa_semana'         => $esSemana,
            'valor_lista_persona'        => $valorLista,
            'recargo_verano_persona'     => $recargo,
            'valor_persona'              => $valorPersona,
            'valor_persona_formato'      => self::fmt($valorPersona),
            'total_programa'             => $totalPrograma,
            'total_programa_formato'     => self::fmt($totalPrograma),
            'masajes_extra'              => $masajes,
            'precio_masaje'              => $extras['masaje'],
            'total_masajes'              => $totalMasajes,
            'total_masajes_formato'      => self::fmt($totalMasajes),
            'desayuno_once'              => $dyo,
            'precio_desayuno_once'       => $extras['desayuno_once'],
            'total_desayuno_once'        => $totalDyo,
            'total_desayuno_once_formato'=> self::fmt($totalDyo),
            'total'                      => $total,
            'total_formato'              => self::fmt($total),
            'tipo_pago'                  => $tipoPago ?: null,
            'pago_ahora_pct'             => $pct,
            'pago_ahora'                 => $pagoAhora,
            'pago_ahora_formato'         => self::fmt($pagoAhora),
            'saldo'                      => $saldo,
            'saldo_formato'              => self::fmt($saldo),
            'notas'                      => $notas,
        ];
    }

    /** Solo montos (sin validar reglas), para BotReservaController. */
    public static function calcularMontos(int $programaId, string $fecha, int $personas, int $masajes, int $dyo, $tipoPago): array
    {
        return self::cotizar([
            'programa_id'   => $programaId,
            'fecha'         => $fecha,
            'personas'      => $personas,
            'masajes_extra' => $masajes,
            'desayuno_once' => $dyo,
            'tipo_pago'     => $tipoPago,
        ], false);
    }
}
