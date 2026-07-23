<?php

namespace App\Http\Controllers;

use App\CategoriaCompra;
use App\Egreso;
use App\Proveedor;
use App\Services\SiiService;
use App\TipoDocumento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SiiController
 *
 * Modulo de importacion de documentos de compra desde el SII
 * a traves de API Gateway Chile.
 *
 * Flujo:
 *   1. index()         -> seleccionar periodo (mes/anio)
 *   2. listar()        -> consulta RCV SII y muestra documentos del periodo
 *   3. importarTodo()  -> importa todos los DTEs pendientes del periodo
 *   4. importar()      -> crea Egreso(s) a partir de DTEs seleccionados
 *   5. contribuyente() -> busca datos de un RUT en SII (AJAX)
 */
class SiiController extends Controller
{
    private $sii;

    public function __construct(SiiService $sii)
    {
        $this->sii = $sii;
    }

    // -------------------------------------------------------------------------
    // 1. INDEX: seleccion de periodo
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes', now()->month);

        $credencialesOk = $this->sii->credencialesConfiguradas();

        return view('themes.backoffice.pages.sii.index', compact('anio', 'mes', 'credencialesOk'));
    }

    // -------------------------------------------------------------------------
    // 2. LISTAR: consulta RCV y muestra documentos
    // -------------------------------------------------------------------------

    public function listar(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:2020|max:2099',
            'mes'  => 'required|integer|min:1|max:12',
        ]);

        $anio = (int) $request->anio;
        $mes  = (int) $request->mes;

        if (!$this->sii->credencialesConfiguradas()) {
            return back()->with('error', 'Las credenciales de SII no estan configuradas. Revisa SII_API_KEY y SII_RUT_EMPRESA.');
        }

        $resultado = $this->sii->listarCompras($anio, $mes);

        if (!$resultado['ok'] && empty($resultado['data'])) {
            return back()->with('error', 'Error al consultar SII: ' . $resultado['error']);
        }

        $documentos = collect($resultado['data']);

        // Folios ya importados este periodo (para deduplicacion)
        $yaImportados = DB::table('egresos')
            ->where('fuente', 'sii')
            ->whereYear('fecha_egreso', $anio)
            ->whereMonth('fecha_egreso', $mes)
            ->pluck('numero_documento')
            ->toArray();

        $documentos = $documentos->map(function ($doc) use ($yaImportados) {
            $doc['ya_importado'] = in_array($doc['folio'], $yaImportados);
            return $doc;
        });

        // Resumen por proveedor
        $totalesPorProveedor = $documentos
            ->groupBy('rut_emisor')
            ->map(function ($docs) {
                return [
                    'razon_social' => $docs->first()['razon_social'] ?? '-',
                    'rut_emisor'   => $docs->first()['rut_emisor'],
                    'cant'         => $docs->count(),
                    'pendientes'   => $docs->where('ya_importado', false)->count(),
                    'neto'         => $docs->sum('monto_neto'),
                    'iva'          => $docs->sum('monto_iva'),
                    'total'        => $docs->sum('monto_total'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $categorias     = CategoriaCompra::all();
        $tipoDocumentos = TipoDocumento::all();

        $nombreMes = ucfirst(Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM [de] YYYY'));

        return view('themes.backoffice.pages.sii.listar', compact(
            'documentos', 'anio', 'mes', 'nombreMes',
            'categorias', 'tipoDocumentos', 'yaImportados',
            'totalesPorProveedor'
        ));
    }

    // -------------------------------------------------------------------------
    // 3. IMPORTAR TODO: importa todos los DTEs pendientes del periodo
    // -------------------------------------------------------------------------

    public function importarTodo(Request $request)
    {
        $request->validate([
            'anio'            => 'required|integer|min:2020|max:2099',
            'mes'             => 'required|integer|min:1|max:12',
            'categoria_id'    => 'required|exists:categorias_compras,id',
            'subcategoria_id' => 'required|exists:subcategorias_compras,id',
        ]);

        $anio     = (int) $request->anio;
        $mes      = (int) $request->mes;
        $catId    = (int) $request->categoria_id;
        $subCatId = (int) $request->subcategoria_id;

        $resultado = $this->sii->listarCompras($anio, $mes);

        if (!$resultado['ok'] && empty($resultado['data'])) {
            return redirect()->route('backoffice.sii.listar', ['anio' => $anio, 'mes' => $mes])
                ->with('error', 'Error al consultar SII: ' . ($resultado['error'] ?? 'Sin datos'));
        }

        $importados = 0;
        $omitidos   = 0;
        $totalNeto  = 0;
        $totalTotal = 0;

        DB::beginTransaction();
        try {
            foreach ($resultado['data'] as $doc) {
                // Deduplicar por fuente + numero_documento (folio)
                $existe = Egreso::where('fuente', 'sii')
                    ->where('numero_documento', $doc['folio'])
                    ->exists();

                if ($existe) {
                    $omitidos++;
                    continue;
                }

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
                    'neto'              => $doc['monto_neto'] ?: null,
                    'iva'               => $doc['monto_iva']  ?: null,
                    'total'             => $doc['monto_total'],
                    'fuente'            => 'sii',
                    'estado'            => 'pendiente',
                    'observaciones'     => 'Importado SII RCV - RUT: ' . $doc['rut_emisor'],
                ]);

                $importados++;
                $totalNeto  += (int) ($doc['monto_neto']  ?? 0);
                $totalTotal += (int) ($doc['monto_total'] ?? 0);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('backoffice.sii.listar', ['anio' => $anio, 'mes' => $mes])
                ->with('error', 'Error al importar: ' . $e->getMessage());
        }

        $msg = $importados . ' factura(s) importadas. Neto $'
             . number_format($totalNeto,  0, ',', '.')
             . ' / Total $'
             . number_format($totalTotal, 0, ',', '.');
        if ($omitidos > 0) {
            $msg .= ' (' . $omitidos . ' omitida(s), ya existian)';
        }

        return redirect()->route('backoffice.sii.listar', ['anio' => $anio, 'mes' => $mes])
            ->with('success', $msg);
    }

    // -------------------------------------------------------------------------
    // 4. IMPORTAR: crear egresos desde DTEs seleccionados manualmente
    // -------------------------------------------------------------------------

    public function importar(Request $request)
    {
        $request->validate([
            'documentos'                    => 'required|array|min:1',
            'documentos.*.folio'            => 'required|string',
            'documentos.*.rut_emisor'       => 'required|string',
            'documentos.*.razon_social'     => 'nullable|string',
            'documentos.*.fecha_documento'  => 'required|date',
            'documentos.*.monto_neto'       => 'nullable|integer',
            'documentos.*.monto_iva'        => 'nullable|integer',
            'documentos.*.monto_total'      => 'required|integer|min:1',
            'documentos.*.tipo_documento'   => 'required|integer',
            'documentos.*.categoria_id'     => 'required|exists:categorias_compras,id',
            'documentos.*.subcategoria_id'  => 'required|exists:subcategorias_compras,id',
        ]);

        $importados = 0;
        $omitidos   = 0;

        DB::beginTransaction();
        try {
            foreach ($request->documentos as $doc) {
                $existe = Egreso::where('fuente', 'sii')
                    ->where('numero_documento', $doc['folio'])
                    ->exists();

                if ($existe) {
                    $omitidos++;
                    continue;
                }

                $proveedor = $this->resolverProveedor(
                    $doc['rut_emisor'],
                    $doc['razon_social'] ?? null
                );

                $tipoDocId = $this->resolverTipoDocumento($doc['tipo_documento']);

                Egreso::create([
                    'tipo_documento_id' => $tipoDocId,
                    'categoria_id'      => $doc['categoria_id'],
                    'subcategoria_id'   => $doc['subcategoria_id'],
                    'proveedor_id'      => $proveedor ? $proveedor->id : null,
                    'descripcion'       => trim(($doc['razon_social'] ?? '') . ' - Folio ' . $doc['folio']),
                    'fecha_egreso'      => $doc['fecha_documento'],
                    'numero_documento'  => $doc['folio'],
                    'neto'              => $doc['monto_neto'] ?: null,
                    'iva'               => $doc['monto_iva']  ?: null,
                    'total'             => $doc['monto_total'],
                    'fuente'            => 'sii',
                    'estado'            => 'pendiente',
                    'observaciones'     => 'Importado desde SII RCV - RUT emisor: ' . $doc['rut_emisor'],
                ]);

                $importados++;
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }

        $msg = $importados . ' documento(s) importado(s) correctamente.';
        if ($omitidos > 0) {
            $msg .= ' ' . $omitidos . ' omitido(s) porque ya existian.';
        }

        return redirect()->route('backoffice.sii.index')->with('success', $msg);
    }

    // -------------------------------------------------------------------------
    // 5. CONTRIBUYENTE: busqueda AJAX por RUT
    // -------------------------------------------------------------------------

    public function contribuyente(Request $request)
    {
        $request->validate(['rut' => 'required|string']);

        $resultado = $this->sii->buscarContribuyente($request->rut);

        return response()->json($resultado);
    }

    // -------------------------------------------------------------------------
    // PRIVADO: helpers
    // -------------------------------------------------------------------------

    private function resolverProveedor($rut, $razonSocial)
    {
        $proveedor = Proveedor::where('rut', $rut)->first();

        if (!$proveedor && $razonSocial) {
            $proveedor = Proveedor::create([
                'nombre' => $razonSocial,
                'rut'    => $rut,
            ]);
        }

        return $proveedor;
    }

    private function resolverTipoDocumento($codigoSii)
    {
        $mapa = [
            33 => 'Factura',
            34 => 'Factura',
            39 => 'Boleta',
            46 => 'Liquidacion',
            56 => 'Nota de Debito',
            61 => 'Nota de Credito',
        ];

        $nombre = isset($mapa[$codigoSii]) ? $mapa[$codigoSii] : null;
        if (!$nombre) return null;

        $tipo = \App\TipoDocumento::where('nombre', 'like', '%' . $nombre . '%')->first();
        return $tipo ? $tipo->id : null;
    }

    // -------------------------------------------------------------------------
    // 6. RESUMEN: resumen mensual de egresos SII por año
    // -------------------------------------------------------------------------

    public function resumen(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);

        // Años disponibles (desde primer egreso SII registrado hasta año actual)
        $anioMin = (int) (Egreso::where('fuente', 'sii')->min(DB::raw('YEAR(fecha_egreso)')) ?? now()->year);
        $anios   = range(now()->year, $anioMin);

        // Datos importados por mes desde egresos con fuente=sii
        $importadosPorMes = Egreso::where('fuente', 'sii')
            ->whereYear('fecha_egreso', $anio)
            ->selectRaw('MONTH(fecha_egreso) as mes, COUNT(*) as documentos, SUM(COALESCE(neto,0)) as neto, SUM(COALESCE(iva,0)) as iva, SUM(total) as total')
            ->groupBy(DB::raw('MONTH(fecha_egreso)'))
            ->get()
            ->keyBy('mes');

        $nombresMeses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo',  6 => 'Junio',   7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $mesMax = ($anio === (int) now()->year) ? (int) now()->month : 12;
        $meses  = [];

        for ($m = 1; $m <= 12; $m++) {
            $fila = $importadosPorMes->get($m);
            $meses[] = [
                'mes'        => $m,
                'nombre'     => $nombresMeses[$m] . ' ' . $anio,
                'importado'  => $fila !== null,
                'documentos' => $fila ? (int) $fila->documentos : 0,
                'neto'       => $fila ? (int) $fila->neto  : 0,
                'iva'        => $fila ? (int) $fila->iva   : 0,
                'total'      => $fila ? (int) $fila->total : 0,
            ];
        }

        return view('themes.backoffice.pages.sii.resumen', compact('anio', 'anios', 'meses'));
    }

    // -------------------------------------------------------------------------
    // 7. DETALLE-MES: listado de egresos SII de un mes específico
    // -------------------------------------------------------------------------

    public function detalleMes(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes', now()->month);

        $egresos = Egreso::with(['proveedor', 'tipoDocumento', 'categoria', 'subcategoria'])
            ->where('fuente', 'sii')
            ->whereYear('fecha_egreso', $anio)
            ->whereMonth('fecha_egreso', $mes)
            ->orderBy('fecha_egreso', 'desc')
            ->get();

        $nombresMeses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo',  6 => 'Junio',   7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $nombreMes = ($nombresMeses[$mes] ?? '') . ' ' . $anio;

        $totales = [
            'documentos' => $egresos->count(),
            'neto'       => $egresos->sum('neto'),
            'iva'        => $egresos->sum('iva'),
            'total'      => $egresos->sum('total'),
        ];

        return view('themes.backoffice.pages.sii.detalle-mes', compact(
            'anio', 'mes', 'nombreMes', 'egresos', 'totales'
        ));
    }

    // -------------------------------------------------------------------------
    // 8. IMPORTAR-DIRECTO: importa un mes completo del RCV (vía AJAX)
    // -------------------------------------------------------------------------

    public function importarDirecto(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:2020|max:2099',
            'mes'  => 'required|integer|min:1|max:12',
        ]);

        $anio = (int) $request->anio;
        $mes  = (int) $request->mes;

        $resultado = $this->sii->listarCompras($anio, $mes);

        if (!$resultado['ok']) {
            return response()->json(['ok' => false, 'error' => $resultado['error'] ?? 'Error SII'], 422);
        }

        $documentos = $resultado['data'] ?? [];
        $importados = 0;
        $omitidos   = 0;
        $totalNeto  = 0;
        $totalIva   = 0;
        $totalMonto = 0;

        DB::beginTransaction();
        try {
            foreach ($documentos as $doc) {
                $folio = $doc['folio'] ?? ($doc['numero_documento'] ?? null);
                if (!$folio) continue;

                $existe = Egreso::where('fuente', 'sii')
                    ->where('numero_documento', $folio)
                    ->exists();

                if ($existe) {
                    $omitidos++;
                    $totalNeto  += (int) ($doc['monto_neto']   ?? 0);
                    $totalIva   += (int) ($doc['monto_iva']    ?? 0);
                    $totalMonto += (int) ($doc['monto_total']  ?? 0);
                    continue;
                }

                $rut          = $doc['rut_emisor']    ?? null;
                $razonSocial  = $doc['razon_social']  ?? null;
                $tipoDocCodigo = (int) ($doc['tipo_documento'] ?? 33);
                $monto        = (int) ($doc['monto_total'] ?? 0);
                $neto         = (int) ($doc['monto_neto']  ?? 0);
                $iva          = (int) ($doc['monto_iva']   ?? 0);
                $fecha        = $doc['fecha_documento'] ?? now()->format('Y-m-d');

                $proveedor = $rut ? $this->resolverProveedor($rut, $razonSocial) : null;
                $tipoDocId = $this->resolverTipoDocumento($tipoDocCodigo);

                Egreso::create([
                    'tipo_documento_id' => $tipoDocId,
                    'proveedor_id'      => $proveedor ? $proveedor->id : null,
                    'descripcion'       => trim(($razonSocial ?? '') . ' - Folio ' . $folio),
                    'fecha_egreso'      => $fecha,
                    'numero_documento'  => $folio,
                    'neto'              => $neto ?: null,
                    'iva'               => $iva  ?: null,
                    'total'             => $monto,
                    'fuente'            => 'sii',
                    'estado'            => 'pendiente',
                    'observaciones'     => 'Auto-importado RCV ' . $anio . '-' . sprintf('%02d', $mes),
                ]);

                $importados++;
                $totalNeto  += $neto;
                $totalIva   += $iva;
                $totalMonto += $monto;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'ok'         => true,
            'importados' => $importados,
            'omitidos'   => $omitidos,
            'docs'       => $importados + $omitidos,
            'neto'       => $totalNeto,
            'iva'        => $totalIva,
            'total'      => $totalMonto,
        ]);
    }

    // -------------------------------------------------------------------------
    // 9. GASTOS-SEMANA: egresos SII de la semana actual
    // -------------------------------------------------------------------------

    public function gastosSemana(Request $request)
    {
        $inicio = now()->startOfWeek();
        $fin    = now()->endOfWeek();

        $egresos = Egreso::with(['proveedor', 'tipoDocumento'])
            ->where('fuente', 'sii')
            ->whereBetween('fecha_egreso', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->orderBy('fecha_egreso', 'desc')
            ->get();

        $totales = [
            'documentos' => $egresos->count(),
            'neto'       => $egresos->sum('neto'),
            'iva'        => $egresos->sum('iva'),
            'total'      => $egresos->sum('total'),
        ];

        return view('themes.backoffice.pages.sii.gastos-semana', compact(
            'egresos', 'totales', 'inicio', 'fin'
        ));
    }

    // -------------------------------------------------------------------------
    // 10. DEBUG-RAW: muestra respuesta cruda del SII (solo dev)
    // -------------------------------------------------------------------------

    public function debugRaw(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes', now()->month);

        $resultado = $this->sii->listarCompras($anio, $mes);

        return response()->json($resultado);
    }
}
