<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * EgresoApiController
 *
 * Ingreso simple y directo de egresos (sin escaneo IA).
 * Útil para gastos de insumos, servicios y cualquier gasto no automatizable.
 *
 * GET  /api/egresos/form-data   → Listas para el formulario (categorías, proveedores, etc.)
 * POST /api/egresos              → Crear egreso rápido con items opcionales
 * GET  /api/egresos/{id}         → Ver detalle de un egreso
 * GET  /api/egresos              → Listar egresos con filtros
 */
class EgresoApiController extends Controller
{
    // -------------------------------------------------------------------------
    // DATOS PARA EL FORMULARIO
    // -------------------------------------------------------------------------

    public function formData()
    {
        return response()->json([
            'ok'             => true,
            'categorias'     => DB::table('categorias_compras')->select('id', 'nombre')->orderBy('nombre')->get(),
            'subcategorias'  => DB::table('subcategorias_compras')->select('id', 'categoria_id', 'nombre')->orderBy('nombre')->get(),
            'proveedores'    => DB::table('proveedores')->select('id', 'nombre', 'rut')->orderBy('nombre')->get(),
            'tipos_documento'=> DB::table('tipos_documentos')->select('id', 'nombre')->get(),
            'metodos_pago'   => [
                'efectivo', 'transferencia', 'tarjeta_debito',
                'tarjeta_credito', 'cheque', 'credito_proveedor',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // CREAR EGRESO (manual o desde scan confirmado)
    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_documento_id'  => 'required|integer|exists:tipos_documentos,id',
            'categoria_id'       => 'required|integer|exists:categorias_compras,id',
            'subcategoria_id'    => 'required|integer|exists:subcategorias_compras,id',
            'proveedor_id'       => 'nullable|integer|exists:proveedores,id',
            'proveedor_nombre'   => 'nullable|string|max:150',
            'fecha'              => 'required|date',
            'numero_documento'   => 'nullable|string|max:100',
            'neto'               => 'nullable|integer|min:0',
            'iva'                => 'nullable|integer|min:0',
            'impuesto_incluido'  => 'nullable|integer|min:0',
            'total'              => 'required|integer|min:1',
            'metodo_pago'        => 'nullable|string|in:efectivo,transferencia,tarjeta_debito,tarjeta_credito,cheque,credito_proveedor',
            'estado'             => 'nullable|string|in:pendiente,pagado,anulado',
            'fuente'             => 'nullable|string|in:manual,home_assistant,ai_scan,importacion',
            'observaciones'      => 'nullable|string',
            'items'              => 'nullable|array',
            'items.*.descripcion'     => 'required_with:items|string|max:500',
            'items.*.cantidad'        => 'nullable|numeric|min:0',
            'items.*.unidad'          => 'nullable|string|max:50',
            'items.*.precio_unitario' => 'nullable|integer|min:0',
            'items.*.descuento'       => 'nullable|integer|min:0',
            'items.*.subtotal'        => 'nullable|integer|min:0',
        ], [
            'tipo_documento_id.required' => 'El tipo de documento es obligatorio.',
            'categoria_id.required'      => 'La categoría es obligatoria.',
            'subcategoria_id.required'   => 'La subcategoría es obligatoria.',
            'total.required'             => 'El total es obligatorio.',
            'total.min'                  => 'El total debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        // Auto-calcular total si no viene pero sí vienen neto+iva
        $neto  = (int) ($request->neto ?? 0);
        $iva   = (int) ($request->iva ?? 0);
        $total = (int) $request->total;

        // Si total = 0 pero tenemos neto+iva, calculamos
        if ($total === 0 && $neto > 0) {
            $total = $neto + $iva + (int)($request->impuesto_incluido ?? 0);
        }

        // Generar descripción automática si no viene
        $descripcion = $request->descripcion;
        if (empty($descripcion)) {
            $partes = [];
            if ($request->proveedor_nombre) $partes[] = $request->proveedor_nombre;
            elseif ($request->proveedor_id) {
                $prov = DB::table('proveedores')->find($request->proveedor_id);
                if ($prov) $partes[] = $prov->nombre;
            }
            if ($request->numero_documento) $partes[] = 'N°' . $request->numero_documento;
            $descripcion = implode(' – ', $partes) ?: 'Egreso manual';
        }

        DB::beginTransaction();
        try {
            $egresoId = DB::table('egresos')->insertGetId([
                'tipo_documento_id' => $request->tipo_documento_id,
                'categoria_id'      => $request->categoria_id,
                'subcategoria_id'   => $request->subcategoria_id,
                'proveedor_id'      => $request->proveedor_id,
                'neto'              => $neto,
                'iva'               => $iva,
                'impuesto_incluido' => (int) ($request->impuesto_incluido ?? 0),
                'total'             => $total,
                'fecha'             => $request->fecha,
                // Columnas migration_002
                'descripcion'       => $descripcion,
                'fecha_egreso'      => $request->fecha,
                'numero_documento'  => $request->numero_documento,
                'metodo_pago'       => $request->metodo_pago,
                'estado'            => $request->estado ?? 'pendiente',
                'fuente'            => $request->fuente ?? 'manual',
                'observaciones'     => $request->observaciones,
                'user_id'           => auth()->id(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // Guardar ítems
            if ($request->items && count($request->items) > 0) {
                $items = array_map(fn($item) => [
                    'egreso_id'       => $egresoId,
                    'descripcion'     => $item['descripcion'],
                    'unidad'          => $item['unidad'] ?? null,
                    'cantidad'        => $item['cantidad'] ?? 1,
                    'precio_unitario' => (int) ($item['precio_unitario'] ?? 0),
                    'descuento'       => (int) ($item['descuento'] ?? 0),
                    'subtotal'        => (int) ($item['subtotal'] ?? 0),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ], $request->items);
                DB::table('egreso_items')->insert($items);
            }

            DB::commit();

            return response()->json([
                'ok'        => true,
                'egreso_id' => $egresoId,
                'total'     => $total,
                'mensaje'   => 'Egreso registrado correctamente.',
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'ok'    => false,
                'error' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // LISTAR EGRESOS CON FILTROS
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = DB::table('egresos as e')
            ->leftJoin('categorias_compras as c',    'e.categoria_id',     '=', 'c.id')
            ->leftJoin('subcategorias_compras as sc', 'e.subcategoria_id', '=', 'sc.id')
            ->leftJoin('proveedores as p',            'e.proveedor_id',    '=', 'p.id')
            ->leftJoin('tipos_documentos as td',      'e.tipo_documento_id','=', 'td.id')
            ->select(
                'e.id', 'e.fecha', 'e.fecha_egreso', 'e.numero_documento',
                'e.neto', 'e.iva', 'e.total', 'e.estado', 'e.fuente', 'e.descripcion',
                'c.nombre as categoria', 'sc.nombre as subcategoria',
                'p.nombre as proveedor', 'td.nombre as tipo_documento'
            );

        if ($request->filled('desde'))       $query->where('e.fecha_egreso', '>=', $request->desde);
        if ($request->filled('hasta'))       $query->where('e.fecha_egreso', '<=', $request->hasta);
        if ($request->filled('categoria_id'))$query->where('e.categoria_id', $request->categoria_id);
        if ($request->filled('fuente'))      $query->where('e.fuente', $request->fuente);
        if ($request->filled('estado'))      $query->where('e.estado', $request->estado);

        $total = $query->count();
        $egresos = $query->orderByDesc('e.fecha_egreso')->paginate(50);

        return response()->json([
            'ok'      => true,
            'total'   => $total,
            'egresos' => $egresos,
        ]);
    }

    // -------------------------------------------------------------------------
    // VER DETALLE
    // -------------------------------------------------------------------------

    public function show($id)
    {
        $egreso = DB::table('egresos as e')
            ->leftJoin('categorias_compras as c',     'e.categoria_id',     '=', 'c.id')
            ->leftJoin('subcategorias_compras as sc',  'e.subcategoria_id', '=', 'sc.id')
            ->leftJoin('proveedores as p',             'e.proveedor_id',    '=', 'p.id')
            ->leftJoin('tipos_documentos as td',       'e.tipo_documento_id','=', 'td.id')
            ->select('e.*', 'c.nombre as categoria', 'sc.nombre as subcategoria', 'p.nombre as proveedor', 'td.nombre as tipo_documento')
            ->where('e.id', $id)
            ->first();

        if (!$egreso) {
            return response()->json(['ok' => false, 'error' => 'Egreso no encontrado.'], 404);
        }

        $items = DB::table('egreso_items')->where('egreso_id', $id)->get();
        $docs  = DB::table('egreso_documentos')->where('egreso_id', $id)->get()
            ->map(function ($d) {
                $d->datos_extraidos = $d->datos_extraidos ? json_decode($d->datos_extraidos) : null;
                return $d;
            });

        return response()->json([
            'ok'     => true,
            'egreso' => $egreso,
            'items'  => $items,
            'docs'   => $docs,
        ]);
    }
}
