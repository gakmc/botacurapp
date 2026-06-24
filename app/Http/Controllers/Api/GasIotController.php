<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GasCompra;
use App\Models\GasInstalacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * GasIotController
 *
 * Endpoint único para el registro de eventos de gas desde Home Assistant.
 * POST /api/iot/gas/registrar
 *
 * Maneja dos operaciones:
 *   - pago_proveedor:       Compra de cilindros → gas_compras + egresos
 *   - instalacion_cilindro: Cambio de cilindro  → gas_instalaciones
 */
class GasIotController extends Controller
{
    // -------------------------------------------------------------------------
    // ENTRY POINT
    // -------------------------------------------------------------------------

    public function registrar(Request $request)
    {
        $tipo = $request->input('tipo_operacion');

        switch ($tipo) {
            case 'pago_proveedor':
                return $this->pagoProveedor($request);

            case 'instalacion_cilindro':
                return $this->instalacionCilindro($request);

            default:
                return response()->json([
                    'ok'      => false,
                    'error'   => 'tipo_operacion inválido. Use: pago_proveedor | instalacion_cilindro',
                ], 422);
        }
    }

    // -------------------------------------------------------------------------
    // CASO A: PAGO AL PROVEEDOR
    // Guarda en: gas_compras + egresos (BD principal)
    // -------------------------------------------------------------------------

    private function pagoProveedor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proveedor'          => 'nullable|string|max:150',
            'fecha_compra'       => 'required|date',
            'valor_cilindro'     => 'required|integer|min:1',
            'cantidad_cilindros' => 'required|integer|min:1',
            'kg_cilindro'        => 'nullable|numeric|min:0',
            'documento'          => 'nullable|string|max:120',
            'observacion'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok'     => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $valorUnit   = (int) $request->valor_cilindro;
        $cantidad    = (int) $request->cantidad_cilindros;
        $totalClp    = $valorUnit * $cantidad;
        $fecha       = $request->fecha_compra;
        $proveedor   = $request->proveedor ?? 'Sin nombre';

        DB::beginTransaction();
        try {
            // 1. Crear egreso en tabla principal
            $egreso = DB::table('egresos')->insertGetId([
                'descripcion'      => "Compra gas – {$proveedor} ({$cantidad} cilindro(s) × \${$valorUnit})",
                'total'            => $totalClp,
                'fecha_egreso'     => $fecha,
                'numero_documento' => $request->documento,
                'metodo_pago'      => null,
                'estado'           => 'pendiente',
                'fuente'           => 'home_assistant',
                'observaciones'    => $request->observacion,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // 2. Crear registro en gas_compras
            $compra = GasCompra::create([
                'proveedor_nombre'    => $proveedor,
                'fecha_compra'        => $fecha,
                'valor_unitario_clp'  => $valorUnit,
                'cantidad_cilindros'  => $cantidad,
                'kg_cilindro'         => $request->kg_cilindro ?? 0,
                'total_clp'           => $totalClp,
                'documento'           => $request->documento,
                'observacion'         => $request->observacion,
                'egreso_id'           => $egreso,
                'origen'              => 'home_assistant',
                'estado'              => 'comprado',
            ]);

            DB::commit();

            return response()->json([
                'ok'          => true,
                'operacion'   => 'pago_proveedor',
                'gas_compra'  => [
                    'id'        => $compra->id,
                    'total_clp' => $totalClp,
                    'proveedor' => $proveedor,
                    'fecha'     => $fecha,
                ],
                'egreso_id'   => $egreso,
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
    // CASO B: INSTALACIÓN DE CILINDRO
    // Guarda en: gas_instalaciones (BD IoT)
    // Calcula automáticamente cuánto duró el cilindro anterior
    // -------------------------------------------------------------------------

    private function instalacionCilindro(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lugar'              => 'required|in:tinaja_1,tinaja_2,gas_casa,gas_cocina',
            'fecha_instalacion'  => 'required|date',
            'valor_cilindro'     => 'nullable|integer|min:0',
            'kg_cilindro'        => 'nullable|numeric|min:0',
            'proveedor'          => 'nullable|string|max:150',
            'documento'          => 'nullable|string|max:120',
            'observacion'        => 'nullable|string',
            'gas_compra_id'      => 'nullable|integer',
            'contador_valor'     => 'nullable|numeric',
            'contador_unidad'    => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok'     => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $lugar  = $request->lugar;
        $fechaNueva = $request->fecha_instalacion;

        // Buscar la instalación anterior en ese lugar
        $anterior = GasInstalacion::ultimoEnLugar($lugar);

        $fechaAnterior    = $anterior ? $anterior->fecha_instalacion : null;
        $diasDuracion     = null;

        if ($fechaAnterior) {
            $diasDuracion = (int) \Carbon\Carbon::parse($fechaAnterior)
                                ->diffInDays(\Carbon\Carbon::parse($fechaNueva));
        }

        try {
            $instalacion = GasInstalacion::create([
                'lugar'                      => $lugar,
                'fecha_instalacion'          => $fechaNueva,
                'fecha_instalacion_anterior' => $fechaAnterior,
                'dias_duracion_anterior'     => $diasDuracion,
                'valor_cilindro_clp'         => $request->valor_cilindro,
                'kg_cilindro'                => $request->kg_cilindro,
                'proveedor_nombre'           => $request->proveedor,
                'documento'                  => $request->documento,
                'observacion'                => $request->observacion,
                'gas_compra_id'              => $request->gas_compra_id,
                'contador_anterior_valor'    => $request->contador_valor,
                'contador_anterior_unidad'   => $request->contador_unidad,
                'origen'                     => 'home_assistant',
                'estado'                     => 'instalado',
            ]);

            return response()->json([
                'ok'           => true,
                'operacion'    => 'instalacion_cilindro',
                'instalacion'  => [
                    'id'                    => $instalacion->id,
                    'lugar'                 => $lugar,
                    'fecha_instalacion'     => $fechaNueva,
                    'fecha_anterior'        => $fechaAnterior,
                    'dias_duracion_anterior'=> $diasDuracion,
                ],
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => 'Error al guardar instalación: ' . $e->getMessage(),
            ], 500);
        }
    }
}
