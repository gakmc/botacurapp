<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AguaBoleta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AguaIotController extends Controller
{
    // -------------------------------------------------------------------------
    // Registrar boleta de agua (ingreso manual desde HA)
    // Guarda en: agua_boletas + egresos (BD principal)
    // -------------------------------------------------------------------------
    public function registrar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_boleta'      => 'nullable|string|max:50',
            'periodo'            => 'nullable|string|max:20',
            'fecha_emision'      => 'nullable|date',
            'consumo_m3'         => 'nullable|integer|min:0',
            'monto_consumo'      => 'nullable|integer|min:0',
            'cargo_fijo'         => 'nullable|integer|min:0',
            'ajuste_mes_anterior'=> 'nullable|integer',
            'intereses_atraso'   => 'nullable|integer|min:0',
            'ajuste_mes_actual'  => 'nullable|integer',
            'total_mes'          => 'required|integer|min:0',
            'saldo_anterior'     => 'nullable|integer|min:0',
            'total_a_pagar'      => 'required|integer|min:0',
            'fecha_limite_pago'  => 'nullable|date',
            'documento'          => 'nullable|string|max:120',
            'observacion'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok'     => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $egresoId = DB::table('egresos')->insertGetId([
                'categoria_id'     => 1,  // Gastos Fijos
                'subcategoria_id'  => 11, // Agua
                'descripcion'      => "Boleta agua potable N° {$request->numero_boleta} - {$request->periodo}",
                'total'            => (int) $request->total_a_pagar,
                'fecha_egreso'     => $request->fecha_emision,
                'numero_documento' => $request->numero_boleta,
                'estado'           => 'pendiente',
                'fuente'           => 'agua_iot',
                'observaciones'    => $request->observacion,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $boleta = AguaBoleta::create([
                'egreso_id'           => $egresoId,
                'numero_boleta'       => $request->numero_boleta,
                'periodo'             => $request->periodo,
                'fecha_emision'       => $request->fecha_emision,
                'consumo_m3'          => $request->consumo_m3,
                'monto_consumo'       => $request->monto_consumo,
                'cargo_fijo'          => $request->cargo_fijo,
                'ajuste_mes_anterior' => $request->ajuste_mes_anterior ?? 0,
                'intereses_atraso'    => $request->intereses_atraso ?? 0,
                'ajuste_mes_actual'   => $request->ajuste_mes_actual ?? 0,
                'total_mes'           => $request->total_mes,
                'saldo_anterior'      => $request->saldo_anterior ?? 0,
                'total_a_pagar'       => $request->total_a_pagar,
                'fecha_limite_pago'   => $request->fecha_limite_pago,
                'documento'           => $request->documento,
                'observacion'         => $request->observacion,
                'origen'              => 'home_assistant',
            ]);

            DB::commit();

            return response()->json([
                'ok'        => true,
                'egreso_id' => $egresoId,
                'boleta_id' => $boleta->id,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'ok'    => false,
                'error' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }
}
