<?php

namespace App\Http\Controllers;

use App\HonorarioBte;
use App\Proveedor;
use App\Services\SiiBteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * HonorarioController
 *
 * Módulo BTE (Boletas de Prestación de Servicios de Terceros Electrónicas)
 * recibidas por la empresa como receptor.
 *
 * Rutas:
 *   index()      → listado mensual/anual + resumen
 *   sincronizar()→ descarga BTE del SII y guarda en DB
 *   resumen()    → gasto mensual acumulado por emisor/proveedor
 */
class HonorarioController extends Controller
{
    /** @var SiiBteService */
    private $bte;

    public function __construct(SiiBteService $bte)
    {
        $this->bte = $bte;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. INDEX: listado mensual
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes', now()->month);

        $periodo        = sprintf('%04d%02d', $anio, $mes);
        $credencialesOk = $this->bte->credencialesConfiguradas();

        // BTE del período
        $honorarios = HonorarioBte::where('periodo', $periodo)
            ->orderBy('fecha_emision', 'desc')
            ->get();

        // Resumen del período
        $resumen = [
            'total_bte'      => $honorarios->count(),
            'monto_bruto'    => $honorarios->where('estado', '!=', 'Anulada')->sum('monto_bruto'),
            'monto_retenido' => $honorarios->where('estado', '!=', 'Anulada')->sum('monto_retenido'),
            'monto_pagado'   => $honorarios->where('estado', '!=', 'Anulada')->sum('monto_pagado'),
        ];

        // Última sincronización del período
        $ultimaSync = HonorarioBte::where('periodo', $periodo)
            ->max('sincronizado_at');

        // Tabla resumen anual (por mes)
        $resumenAnual = HonorarioBte::selectRaw(
                'periodo,
                 COUNT(*) as cantidad,
                 SUM(CASE WHEN estado != "Anulada" THEN monto_bruto ELSE 0 END) as total_bruto,
                 SUM(CASE WHEN estado != "Anulada" THEN monto_retenido ELSE 0 END) as total_retenido,
                 SUM(CASE WHEN estado != "Anulada" THEN monto_pagado ELSE 0 END) as total_pagado'
            )
            ->where('periodo', 'like', $anio . '%')
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $nombreMes = ucfirst(Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM [de] YYYY'));

        return view('themes.backoffice.pages.honorarios.index', compact(
            'anio', 'mes', 'periodo', 'credencialesOk',
            'honorarios', 'resumen', 'ultimaSync',
            'resumenAnual', 'nombreMes'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. SINCRONIZAR: descarga del SII y guarda en DB
    // ─────────────────────────────────────────────────────────────────────────

    public function sincronizar(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:2020|max:2099',
            'mes'  => 'required|integer|min:1|max:12',
        ]);

        $anio = (int) $request->anio;
        $mes  = (int) $request->mes;

        if (!$this->bte->credencialesConfiguradas()) {
            return back()->with('error', 'Credenciales SII incompletas. Verifica el .env.');
        }

        $resultado = $this->bte->consultarMensual($anio, $mes);

        if (!$resultado['ok']) {
            return back()->with('error', 'Error al consultar SII: ' . $resultado['error']);
        }

        $btes       = $resultado['data'];
        $insertados = 0;
        $actualizados = 0;
        $ahora      = now();

        DB::beginTransaction();
        try {
            foreach ($btes as $bte) {
                $existe = HonorarioBte::where('folio', $bte['folio'])
                    ->where('rut_emisor', $bte['rut_emisor'])
                    ->first();

                if ($existe) {
                    $existe->update(array_merge($bte, ['sincronizado_at' => $ahora]));
                    $actualizados++;
                } else {
                    // Intentar vincular a proveedor existente por RUT
                    $proveedorId = null;
                    $proveedor   = Proveedor::where('rut', $bte['rut_emisor'])->first();
                    if ($proveedor) {
                        $proveedorId = $proveedor->id;
                    }

                    HonorarioBte::create(array_merge($bte, [
                        'proveedor_id'   => $proveedorId,
                        'sincronizado_at'=> $ahora,
                    ]));
                    $insertados++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al guardar BTE: ' . $e->getMessage());
        }

        $periodo  = sprintf('%04d%02d', $anio, $mes);
        $total    = count($btes);
        $sinDatos = $total === 0 ? ' (sin movimientos en el período)' : '';

        $msg = "Sincronizado {$periodo}: {$total} BTE encontrada(s){$sinDatos}. "
             . "Nuevas: {$insertados}, actualizadas: {$actualizados}.";

        return redirect()
            ->route('backoffice.honorarios.index', ['anio' => $anio, 'mes' => $mes])
            ->with('success', $msg);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. RESUMEN: gasto mensual por emisor
    // ─────────────────────────────────────────────────────────────────────────

    public function resumen(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);

        // Gasto por emisor en el año
        $porEmisor = HonorarioBte::selectRaw(
                'rut_emisor, nombre_emisor, proveedor_id,
                 COUNT(*) as cantidad_bte,
                 SUM(CASE WHEN estado != "Anulada" THEN monto_bruto ELSE 0 END) as total_bruto,
                 SUM(CASE WHEN estado != "Anulada" THEN monto_retenido ELSE 0 END) as total_retenido,
                 SUM(CASE WHEN estado != "Anulada" THEN monto_pagado ELSE 0 END) as total_pagado'
            )
            ->where('periodo', 'like', $anio . '%')
            ->groupBy('rut_emisor', 'nombre_emisor', 'proveedor_id')
            ->orderByRaw('total_bruto DESC')
            ->get();

        // Totales del año
        $totalesAnio = [
            'bruto'    => $porEmisor->sum('total_bruto'),
            'retenido' => $porEmisor->sum('total_retenido'),
            'pagado'   => $porEmisor->sum('total_pagado'),
            'cantidad' => $porEmisor->sum('cantidad_bte'),
        ];

        // Evolución mensual
        $evolucionMensual = HonorarioBte::selectRaw(
                'periodo,
                 SUM(CASE WHEN estado != "Anulada" THEN monto_bruto ELSE 0 END) as total_bruto,
                 SUM(CASE WHEN estado != "Anulada" THEN monto_retenido ELSE 0 END) as total_retenido,
                 COUNT(*) as cantidad'
            )
            ->where('periodo', 'like', $anio . '%')
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        return view('themes.backoffice.pages.honorarios.resumen', compact(
            'anio', 'porEmisor', 'totalesAnio', 'evolucionMensual'
        ));
    }
}
