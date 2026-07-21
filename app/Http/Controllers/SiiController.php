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
}
