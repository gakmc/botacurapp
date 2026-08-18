<?php

namespace App\Console\Commands;

use App\HonorarioBte;
use App\Services\SiiService;
use App\SiiResumenMensual;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula sii_resumen_mensual (estimación F29) del mes en curso, a
 * partir de: ventas (consulta directa al SII), compras (egresos ya
 * importados con fuente=sii) y honorarios (honorarios_bte).
 *
 * Misma lógica que ImpuestoController::sincronizar(), pero invocable
 * por cron. Debe correr DESPUÉS de sii:importar-semana y honorarios:sync
 * para que compras/honorarios reflejen la data más fresca.
 *
 * Uso manual: php artisan sii:sincronizar-f29
 */
class SincronizarF29Mensual extends Command
{
    protected $signature = 'sii:sincronizar-f29 {--anio=} {--mes=}';

    protected $description = 'Recalcula el resumen F29 mensual (ventas SII + compras + honorarios BTE)';

    private $sii;

    public function __construct(SiiService $sii)
    {
        parent::__construct();
        $this->sii = $sii;
    }

    public function handle()
    {
        $anio = (int) ($this->option('anio') ?: now()->year);
        $mes  = (int) ($this->option('mes') ?: now()->month);
        $periodo = sprintf('%04d%02d', $anio, $mes);

        if (!$this->sii->credencialesConfiguradas()) {
            $this->error('Credenciales SII no configuradas.');
            return 1;
        }

        $resultVentas = $this->sii->listarVentas($anio, $mes);
        if (!$resultVentas['ok']) {
            $this->error('Error al consultar ventas SII: ' . $resultVentas['error']);
            return 1;
        }

        $rv = $resultVentas['resumen'];

        $periodoKey = $anio . '-' . sprintf('%02d', $mes);
        $compras = DB::table('egresos')
            ->where('fuente', 'sii')
            ->where('periodo_sii', $periodoKey)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(neto),0) as neto, COALESCE(SUM(iva),0) as iva, COALESCE(SUM(total),0) as total')
            ->first();

        $honorarios = DB::table('honorarios_bte')
            ->where('periodo', $periodo)
            ->where('estado', '!=', 'Anulada')
            ->selectRaw('COALESCE(SUM(monto_bruto),0) as bruto, COALESCE(SUM(monto_retenido),0) as retencion, COALESCE(SUM(monto_pagado),0) as neto')
            ->first();

        $ivaDebito     = (int) $rv['iva'];
        $ivaCredito    = (int) $compras->iva;
        $ivaDiferencia = $ivaDebito - $ivaCredito;

        SiiResumenMensual::updateOrCreate(
            ['periodo' => $periodo],
            [
                'compras_neto'          => (int) $compras->neto,
                'compras_iva'           => (int) $compras->iva,
                'compras_exento'        => 0,
                'compras_total'         => (int) $compras->total,
                'compras_cantidad'      => (int) $compras->cantidad,
                'ventas_neto'           => (int) $rv['neto'],
                'ventas_iva'            => (int) $rv['iva'],
                'ventas_exento'         => (int) $rv['exento'],
                'ventas_total'          => (int) $rv['total'],
                'ventas_cantidad'       => (int) $rv['cantidad'],
                'honorarios_bruto'      => (int) $honorarios->bruto,
                'honorarios_retencion'  => (int) $honorarios->retencion,
                'honorarios_neto'       => (int) $honorarios->neto,
                'iva_debito'            => $ivaDebito,
                'iva_credito'           => $ivaCredito,
                'iva_diferencia'        => $ivaDiferencia,
                'ultima_sincronizacion' => now(),
            ]
        );

        $this->info("F29 {$periodo} sincronizado: {$rv['cantidad']} ventas — total \$" . number_format($rv['total'], 0, ',', '.'));
        return 0;
    }
}
