<?php

namespace App\Http\Controllers;

use App\HonorarioBte;
use App\SiiResumenMensual;
use App\Services\SiiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ImpuestoController
 *
 * Gestiona la vista de estimación del F29 mensual.
 * Todos los datos provienen exclusivamente del SII:
 *   - Ventas RCV  → base PPM
 *   - Compras RCV → IVA Crédito (ya importado en egresos)
 *   - BTE         → Retenciones de honorarios
 *
 * Empresa exenta de IVA: no hay débito fiscal.
 * F29 a pagar = PPM (0.25% × ventas) + Retenciones BTE
 *
 * Compatible Laravel 6 / PHP 7.2.
 */
class ImpuestoController extends Controller
{
    const TASA_PPM = 0.0025; // 0.25%

    /** @var SiiService */
    private $sii;

    public function __construct(SiiService $sii)
    {
        $this->sii = $sii;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX — Vista principal F29
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes',  now()->month);

        $periodo   = sprintf('%04d%02d', $anio, $mes);
        $inicio    = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin       = Carbon::create($anio, $mes, 1)->endOfMonth();
        $esActual  = ($anio == now()->year && $mes == now()->month);
        $nombreMes = $inicio->locale('es')->isoFormat('MMMM YYYY');

        $credencialesOk = $this->sii->credencialesConfiguradas();

        // ── Leer resumen SII del período ──────────────────────────────────────
        $resumen = SiiResumenMensual::where('periodo', $periodo)->first();

        // ── Compras IVA desde egresos (fuente=sii) ────────────────────────────
        $periodoKey = $anio . '-' . sprintf('%02d', $mes);
        $creditoFiscal = (int) DB::table('egresos')
            ->where('fuente', 'sii')
            ->where('periodo_sii', $periodoKey)
            ->sum('iva');

        // ── Retenciones BTE ───────────────────────────────────────────────────
        $retencionBte = (int) HonorarioBte::where('periodo', $periodo)
            ->where('estado', '!=', 'Anulada')
            ->sum('monto_retenido');

        $bteCantidad = (int) HonorarioBte::where('periodo', $periodo)
            ->where('estado', '!=', 'Anulada')
            ->count();

        // ── Ventas del período (desde sii_resumen_mensual) ───────────────────
        $ventasTotal  = $resumen ? (int) $resumen->ventas_total  : 0;
        $ventasNeto   = $resumen ? (int) $resumen->ventas_neto   : 0;
        $ventasExento = $resumen ? (int) $resumen->ventas_exento : 0;
        $ventasCant   = $resumen ? (int) $resumen->ventas_cantidad : 0;

        // Base PPM = total ventas (exentas + afectas sin IVA)
        $basePpm = $ventasTotal;
        $ppm     = (int) round($basePpm * self::TASA_PPM);

        // ── Total F29 estimado ────────────────────────────────────────────────
        $totalF29 = $ppm + $retencionBte;

        // ── Proyección si es mes actual (histórico de últimos 3 meses) ────────
        $proyeccion = null;
        if ($esActual) {
            $promedioVentas = $this->promedioVentas3Meses($anio, $mes);
            $ppmProyectado  = (int) round($promedioVentas * self::TASA_PPM);
            $proyeccion = [
                'promedio_ventas'  => $promedioVentas,
                'ppm_proyectado'   => $ppmProyectado,
                'total_proyectado' => $ppmProyectado + $retencionBte,
                'sincronizado'     => $resumen !== null,
                'ultima_sync'      => $resumen ? $resumen->ultima_sincronizacion : null,
            ];
        }

        // ── BTE por semana (mes actual) ───────────────────────────────────────
        $bteSemanales = $this->bteSemanales($anio, $mes, $inicio, $fin);

        // ── Resumen anual ─────────────────────────────────────────────────────
        $resumenAnual = $this->resumenAnual($anio);

        $mesesNombres = [
            1  => 'Enero',    2  => 'Febrero',   3  => 'Marzo',
            4  => 'Abril',    5  => 'Mayo',       6  => 'Junio',
            7  => 'Julio',    8  => 'Agosto',     9  => 'Septiembre',
            10 => 'Octubre',  11 => 'Noviembre',  12 => 'Diciembre',
        ];

        return view('themes.backoffice.pages.impuesto.index', compact(
            'anio', 'mes', 'mesesNombres', 'nombreMes', 'periodo', 'esActual',
            'credencialesOk', 'resumen',
            'ventasTotal', 'ventasNeto', 'ventasExento', 'ventasCant',
            'creditoFiscal', 'retencionBte', 'bteCantidad',
            'basePpm', 'ppm', 'totalF29',
            'proyeccion', 'bteSemanales', 'resumenAnual'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SINCRONIZAR — Importa ventas SII y actualiza sii_resumen_mensual
    // ─────────────────────────────────────────────────────────────────────────

    public function sincronizar(Request $request)
    {
        $anio    = (int) $request->input('anio', now()->year);
        $mes     = (int) $request->input('mes',  now()->month);
        $periodo = sprintf('%04d%02d', $anio, $mes);

        try {
            // 1. Ventas desde SII
            $resultVentas = $this->sii->listarVentas($anio, $mes);

            if (!$resultVentas['ok']) {
                return back()->with('error', 'Error al consultar ventas SII: ' . $resultVentas['error']);
            }

            $rv = $resultVentas['resumen'];

            // 2. Compras: leer de egresos ya importados
            $periodoKey = $anio . '-' . sprintf('%02d', $mes);
            $compras = DB::table('egresos')
                ->where('fuente', 'sii')
                ->where('periodo_sii', $periodoKey)
                ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(neto),0) as neto, COALESCE(SUM(iva),0) as iva, COALESCE(SUM(total),0) as total')
                ->first();

            // 3. Honorarios BTE
            $honorarios = DB::table('honorarios_bte')
                ->where('periodo', $periodo)
                ->where('estado', '!=', 'Anulada')
                ->selectRaw('COALESCE(SUM(monto_bruto),0) as bruto, COALESCE(SUM(monto_retenido),0) as retencion, COALESCE(SUM(monto_pagado),0) as neto')
                ->first();

            // 4. Upsert sii_resumen_mensual
            $ivaDebito    = (int) $rv['iva'];
            $ivaCredito   = (int) $compras->iva;
            $ivaDiferencia = $ivaDebito - $ivaCredito; // negativo = remanente

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

            return back()->with('success',
                "F29 sincronizado: {$rv['cantidad']} ventas SII — Total ventas $" .
                number_format($rv['total'], 0, ',', '.')
            );

        } catch (\Throwable $e) {
            Log::error('ImpuestoSincronizar', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error inesperado: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVADO: helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Promedio de ventas de los últimos 3 meses cerrados (para proyección).
     */
    private function promedioVentas3Meses($anio, $mes)
    {
        $totales = [];
        for ($i = 1; $i <= 3; $i++) {
            $ref     = Carbon::create($anio, $mes, 1)->subMonths($i);
            $periodo = $ref->format('Ym');
            $r = SiiResumenMensual::where('periodo', $periodo)->first();
            if ($r && $r->ventas_total > 0) {
                $totales[] = (int) $r->ventas_total;
            }
        }
        return count($totales) > 0 ? (int) round(array_sum($totales) / count($totales)) : 0;
    }

    /**
     * BTE retenciones acumuladas semana a semana para el mes actual.
     */
    private function bteSemanales($anio, $mes, Carbon $inicio, Carbon $fin)
    {
        $semanas = [];
        $cursor  = $inicio->copy()->startOfWeek(Carbon::MONDAY);
        $hoy     = now()->toDateString();
        $acum    = 0;

        // Ajustar cursor si el lunes anterior al inicio cae en mes previo
        if ($cursor->lt($inicio)) {
            $cursor = $inicio->copy();
        }

        while ($cursor->lte($fin)) {
            $inicioSem = $cursor->copy();
            $finSem    = $cursor->copy()->endOfWeek(Carbon::SUNDAY);
            if ($finSem->gt($fin)) {
                $finSem = $fin->copy();
            }

            $retSem = (int) HonorarioBte::where('periodo', sprintf('%04d%02d', $anio, $mes))
                ->where('estado', '!=', 'Anulada')
                ->whereBetween('fecha_emision', [$inicioSem->toDateString(), $finSem->toDateString()])
                ->sum('monto_retenido');

            $acum += $retSem;

            $semanas[] = [
                'label'       => $inicioSem->format('d/m') . ' – ' . $finSem->format('d/m'),
                'retencion'   => $retSem,
                'acumulado'   => $acum,
                'pasada'      => $finSem->toDateString() < $hoy,
                'activa'      => $inicioSem->toDateString() <= $hoy && $finSem->toDateString() >= $hoy,
            ];

            $cursor = $finSem->copy()->addDay();
        }

        return $semanas;
    }

    /**
     * Resumen anual de F29 estimados por mes.
     */
    private function resumenAnual($anio)
    {
        $resumenDb = SiiResumenMensual::where('periodo', 'like', $anio . '%')
            ->orderBy('periodo')
            ->get()
            ->keyBy(function ($r) { return (int) substr($r->periodo, 4, 2); });

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            if (Carbon::create($anio, $m, 1)->gt(now())) {
                $meses[$m] = null;
                continue;
            }

            $periodo = sprintf('%04d%02d', $anio, $m);
            $r       = isset($resumenDb[$m]) ? $resumenDb[$m] : null;

            $ret = (int) HonorarioBte::where('periodo', $periodo)
                ->where('estado', '!=', 'Anulada')
                ->sum('monto_retenido');

            $ventas = $r ? (int) $r->ventas_total : 0;
            $ppm    = (int) round($ventas * self::TASA_PPM);

            $meses[$m] = [
                'ventas'      => $ventas,
                'ppm'         => $ppm,
                'retenciones' => $ret,
                'total'       => $ppm + $ret,
                'sincronizado'=> $r !== null,
            ];
        }

        return $meses;
    }
}
