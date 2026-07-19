<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Egreso;
use App\Services\SiiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SiiAutoController
 *
 * Importación automática del RCV SII sin interacción manual.
 * Diseñado para ejecutarse desde tarea programada (domingo 21:00).
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class SiiAutoController extends Controller
{
    private $sii;

    public function __construct(SiiService $sii)
    {
        $this->sii = $sii;
    }

    // -------------------------------------------------------------------------
    // POST /api/sii/auto-importar
    // Importa documentos del mes actual (o anio+mes indicados).
    // Usa auto-match por nombre de proveedor para subcategoría.
    // -------------------------------------------------------------------------

    public function autoImportar(Request $request)
    {
        if (!$this->sii->credencialesConfiguradas()) {
            return response()->json([
                'ok'    => false,
                'error' => 'Credenciales SII no configuradas.',
            ], 500);
        }

        $anio = (int) ($request->input('anio', now()->year));
        $mes  = (int) ($request->input('mes',  now()->month));

        // ── Categoría por defecto (Gastos Variables) ──────────────────────────
        $catDefault    = DB::table('categorias_compras')->where('nombre', 'Gastos Variables')->first();
        $subCatDefault = $catDefault
            ? DB::table('subcategorias_compras')->where('categoria_id', $catDefault->id)->first()
            : null;

        if (!$catDefault || !$subCatDefault) {
            return response()->json([
                'ok'    => false,
                'error' => 'No existe categoría "Gastos Variables" o no tiene subcategorías. Corre el seeder primero.',
            ], 500);
        }

        $catIdDef    = $catDefault->id;
        $subCatIdDef = $subCatDefault->id;

        // ── Mapa auto-match nombre_proveedor → subcategoría ───────────────────
        $mapaSub = DB::table('subcategorias_compras as sc')
            ->join('categorias_compras as c', 'c.id', '=', 'sc.categoria_id')
            ->select('sc.id as subcategoria_id', 'sc.categoria_id')
            ->addSelect(DB::raw('LOWER(TRIM(sc.nombre)) AS nombre_key'))
            ->get()
            ->keyBy('nombre_key');

        // ── Consultar RCV SII ─────────────────────────────────────────────────
        $resultado = $this->sii->listarCompras($anio, $mes);

        if (!$resultado['ok'] && empty($resultado['data'])) {
            return response()->json([
                'ok'    => false,
                'error' => 'Error al consultar SII: ' . ($resultado['error'] ?? 'sin datos'),
            ], 502);
        }

        $importados = 0;
        $omitidos   = 0;
        $autoMatch  = 0;
        $sinMatch   = 0;

        DB::beginTransaction();
        try {
            foreach ($resultado['data'] as $doc) {
                // Evitar duplicados
                $existe = Egreso::where('fuente', 'sii')
                    ->where('numero_documento', $doc['folio'])
                    ->exists();

                if ($existe) {
                    $omitidos++;
                    continue;
                }

                // Auto-match proveedor → subcategoría
                $key   = mb_strtolower(trim($doc['razon_social'] ?? ''));
                $match = isset($mapaSub[$key]) ? $mapaSub[$key] : null;

                if ($match) {
                    $catId    = $match->categoria_id;
                    $subCatId = $match->subcategoria_id;
                    $autoMatch++;
                } else {
                    $catId    = $catIdDef;
                    $subCatId = $subCatIdDef;
                    $sinMatch++;
                }

                // Resolver proveedor
                $proveedor = $this->resolverProveedor(
                    $doc['rut_emisor'],
                    $doc['razon_social'] ?? null
                );

                $tipoDocId = $this->resolverTipoDocumento($doc['tipo_documento']);

                Egreso::create([
                    'tipo_documento_id' => $tipoDocId,
                    'categoria_id'      => $catId,
                    'subcategoria_id'   => $subCatId,
                    'proveedor_id'      => $proveedor ? $proveedor->id : null,
                    'descripcion'       => trim(($doc['razon_social'] ?? '') . ' - Folio ' . $doc['folio']),
                    'fecha_egreso'      => $doc['fecha_documento'],
                    'numero_documento'  => $doc['folio'],
                    'neto'              => $doc['monto_neto']  ?: null,
                    'iva'               => $doc['monto_iva']   ?: null,
                    'total'             => $doc['monto_total'],
                    'fuente'            => 'sii',
                    'estado'            => 'pendiente',
                    'observaciones'     => 'Auto-importado SII RCV - RUT: ' . $doc['rut_emisor'],
                ]);

                $importados++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'ok'    => false,
                'error' => 'Error al importar: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok'         => true,
            'periodo'    => $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT),
            'importados' => $importados,
            'omitidos'   => $omitidos,
            'auto_match' => $autoMatch,
            'sin_match'  => $sinMatch,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/sii/resumen-semanal
    // Retorna gasto de la semana actual y acumulado del mes.
    // Formato listo para mostrar en reporte de 2 columnas.
    // -------------------------------------------------------------------------

    public function resumenSemanal(Request $request)
    {
        $anio = (int) ($request->input('anio', now()->year));
        $mes  = (int) ($request->input('mes',  now()->month));

        $inicioMes     = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes        = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();
        $inicioSemana  = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $finSemana     = Carbon::now()->endOfDay();

        // ── Gasto del mes por subcategoría ────────────────────────────────────
        $gastoMes = DB::table('egresos as e')
            ->join('subcategorias_compras as sc', 'sc.id', '=', 'e.subcategoria_id')
            ->join('categorias_compras as c', 'c.id', '=', 'e.categoria_id')
            ->whereBetween('e.fecha_egreso', [$inicioMes, $finMes])
            ->where('e.fuente', 'sii')
            ->select(
                'c.nombre as categoria',
                'sc.nombre as subcategoria',
                DB::raw('SUM(e.total) as total_mes')
            )
            ->groupBy('c.nombre', 'sc.nombre')
            ->orderBy('c.nombre')
            ->orderByRaw('SUM(e.total) DESC')
            ->get();

        // ── Gasto de la semana por subcategoría ───────────────────────────────
        $gastoSemana = DB::table('egresos as e')
            ->join('subcategorias_compras as sc', 'sc.id', '=', 'e.subcategoria_id')
            ->join('categorias_compras as c', 'c.id', '=', 'e.categoria_id')
            ->whereBetween('e.fecha_egreso', [$inicioSemana, $finSemana])
            ->where('e.fuente', 'sii')
            ->select(
                'sc.nombre as subcategoria',
                DB::raw('SUM(e.total) as total_semana')
            )
            ->groupBy('sc.nombre')
            ->get()
            ->keyBy('subcategoria');

        // ── Combinar en filas de 2 columnas ──────────────────────────────────
        $filas = [];
        foreach ($gastoMes as $row) {
            $semana = isset($gastoSemana[$row->subcategoria])
                ? (int) $gastoSemana[$row->subcategoria]->total_semana
                : 0;

            $filas[] = [
                'categoria'    => $row->categoria,
                'proveedor'    => $row->subcategoria,
                'total_semana' => $semana,
                'total_mes'    => (int) $row->total_mes,
            ];
        }

        // Totales generales
        $totalMes    = array_sum(array_column($filas, 'total_mes'));
        $totalSemana = array_sum(array_column($filas, 'total_semana'));

        return response()->json([
            'ok'           => true,
            'periodo'      => $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT),
            'semana_desde' => $inicioSemana->toDateString(),
            'semana_hasta' => $finSemana->toDateString(),
            'total_mes'    => $totalMes,
            'total_semana' => $totalSemana,
            'filas'        => $filas,
        ]);
    }

    // -------------------------------------------------------------------------
    // HELPERS (duplicados de SiiController para mantener independencia)
    // -------------------------------------------------------------------------

    private function resolverProveedor(string $rut, $razonSocial)
    {
        return DB::table('proveedores')
            ->where('rut', $rut)
            ->first()
            ?: null;
    }

    private function resolverTipoDocumento(int $tipo)
    {
        $row = DB::table('tipo_documentos')->where('codigo', $tipo)->first();
        return $row ? $row->id : null;
    }
}
