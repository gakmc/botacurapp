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
 * Módulo de importación de documentos de compra desde el SII
 * a través de API Gateway Chile.
 *
 * Flujo:
 *   1. index()       → seleccionar período (mes/año)
 *   2. listar()      → consulta RCV SII y muestra documentos del período
 *   3. importar()    → crea Egreso(s) a partir de DTEs seleccionados
 *   4. contribuyente() → busca datos de un RUT en SII (AJAX)
 */
class SiiController extends Controller
{
    private SiiService $sii;

    public function __construct(SiiService $sii)
    {
        $this->sii = $sii;
    }

    // -------------------------------------------------------------------------
    // 1. INDEX: selección de período
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
            return back()->with('error', 'Las credenciales de SII no están configuradas. Revisa las variables de entorno SII_API_KEY y SII_RUT_EMPRESA.');
        }

        $resultado = $this->sii->listarCompras($anio, $mes);

        if (!$resultado['ok']) {
            return back()->with('error', 'Error al consultar SII: ' . $resultado['error']);
        }

        $documentos = collect($resultado['data']);

        // Marcar cuáles ya están importados (mismo folio + rut_emisor en egresos)
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

        // Datos para los selects del formulario de importación
        $categorias     = CategoriaCompra::all();
        $tipoDocumentos = TipoDocumento::all();

        $nombreMes = ucfirst(Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM [de] YYYY'));

        return view('themes.backoffice.pages.sii.listar', compact(
            'documentos', 'anio', 'mes', 'nombreMes',
            'categorias', 'tipoDocumentos', 'yaImportados'
        ));
    }

    // -------------------------------------------------------------------------
    // 3. IMPORTAR: crear egresos desde DTEs seleccionados
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
        $errores    = [];

        DB::beginTransaction();
        try {
            foreach ($request->documentos as $doc) {

                // Evitar duplicados (mismo folio ya importado)
                $existe = Egreso::where('fuente', 'sii')
                    ->where('numero_documento', $doc['folio'])
                    ->exists();

                if ($existe) {
                    $omitidos++;
                    continue;
                }

                // Buscar o crear proveedor por RUT
                $proveedor = $this->resolverProveedor(
                    $doc['rut_emisor'],
                    $doc['razon_social'] ?? null
                );

                // Determinar tipo_documento_id según código SII
                $tipoDocId = $this->resolverTipoDocumento($doc['tipo_documento']);

                Egreso::create([
                    'tipo_documento_id' => $tipoDocId,
                    'categoria_id'      => $doc['categoria_id'],
                    'subcategoria_id'   => $doc['subcategoria_id'],
                    'proveedor_id'      => $proveedor?->id,
                    'descripcion'       => trim(($doc['razon_social'] ?? '') . ' – Folio ' . $doc['folio']),
                    'fecha_egreso'      => $doc['fecha_documento'],
                    'numero_documento'  => $doc['folio'],
                    'neto'              => $doc['monto_neto'] ?: null,
                    'iva'               => $doc['monto_iva']  ?: null,
                    'total'             => $doc['monto_total'],
                    'fuente'            => 'sii',
                    'estado'            => 'pendiente',
                    'observaciones'     => 'Importado desde SII RCV – RUT emisor: ' . $doc['rut_emisor'],
                ]);

                $importados++;
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }

        $msg = "{$importados} documento(s) importado(s) correctamente.";
        if ($omitidos > 0) {
            $msg .= " {$omitidos} omitido(s) porque ya existían.";
        }

        return redirect()->route('backoffice.sii.index')->with('success', $msg);
    }

    // -------------------------------------------------------------------------
    // 4. CONTRIBUYENTE: búsqueda AJAX por RUT
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

    /**
     * Busca un proveedor por RUT. Si no existe y tiene razón social, lo crea.
     */
    private function resolverProveedor(string $rut, ?string $razonSocial): ?Proveedor
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

    /**
     * Resuelve el tipo_documento_id interno según el código SII.
     * Factura (33) → tipo_documento_id = 2 (según seed existente en tipos_documentos).
     * Si no hay match, retorna null.
     */
    private function resolverTipoDocumento(int $codigoSii): ?int
    {
        // Códigos SII → nombre a buscar en tipos_documentos
        $mapa = [
            33 => 'Factura',
            34 => 'Factura',
            39 => 'Boleta',
            46 => 'Liquidación',
            56 => 'Nota de Débito',
            61 => 'Nota de Crédito',
        ];

        $nombre = $mapa[$codigoSii] ?? null;
        if (!$nombre) return null;

        $tipo = TipoDocumento::where('nombre', 'like', "%{$nombre}%")->first();
        return $tipo?->id;
    }
}
