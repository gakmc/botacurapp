<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * GasReconciliacionService
 *
 * Reconcilia las compras de gas registradas automaticamente via IoT (Home
 * Assistant, fuente "gas_iot") contra la factura real del mismo proveedor
 * que llega despues via sincronizacion SII (fuente "sii"). Cuando calzan
 * dentro de una tolerancia razonable, las compras IoT del periodo quedan
 * marcadas como reconciliadas (reconciliado_con_id) y dejan de contarse
 * aparte en los reportes -- la factura real pasa a ser el egreso oficial
 * de ese periodo, y el estimado IoT queda solo como referencia/auditoria.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class GasReconciliacionService
{
    // Si la diferencia entre lo estimado por IoT y la factura real supera
    // este porcentaje, NO se reconcilia automaticamente -- queda para
    // revision manual (evita ocultar un posible error de cobro o de medicion).
    const TOLERANCIA_PORCENTAJE = 0.15;

    public function reconciliarPeriodo(int $proveedorId, int $anio, int $mes): array
    {
        $periodo = sprintf('%04d-%02d', $anio, $mes);
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finMes    = Carbon::create($anio, $mes, 1)->endOfMonth();

        $facturaReal = DB::table('egresos')
            ->where('proveedor_id', $proveedorId)
            ->where('fuente', 'sii')
            ->whereBetween('fecha_egreso', [$inicioMes, $finMes])
            ->orderBy('fecha_egreso', 'desc')
            ->first();

        if (!$facturaReal) {
            return ['ok' => false, 'motivo' => 'sin_factura_sii', 'periodo' => $periodo];
        }

        $comprasIot = DB::table('egresos')
            ->where('proveedor_id', $proveedorId)
            ->where('fuente', 'gas_iot')
            ->whereNull('reconciliado_con_id')
            ->whereBetween('fecha_egreso', [$inicioMes, $finMes])
            ->get();

        if ($comprasIot->isEmpty()) {
            return ['ok' => false, 'motivo' => 'sin_compras_iot', 'periodo' => $periodo];
        }

        $sumaIot      = (int) $comprasIot->sum('total');
        $totalFactura = (int) $facturaReal->total;
        $diferencia   = abs($sumaIot - $totalFactura);
        $porcentaje   = $totalFactura > 0 ? $diferencia / $totalFactura : 1;

        if ($porcentaje > self::TOLERANCIA_PORCENTAJE) {
            return [
                'ok'                    => false,
                'motivo'                => 'diferencia_fuera_de_tolerancia',
                'periodo'               => $periodo,
                'suma_iot'              => $sumaIot,
                'total_factura'         => $totalFactura,
                'porcentaje_diferencia' => round($porcentaje * 100, 1),
            ];
        }

        DB::table('egresos')
            ->whereIn('id', $comprasIot->pluck('id'))
            ->update([
                'reconciliado_con_id' => $facturaReal->id,
                'updated_at'          => now(),
            ]);

        return [
            'ok'                     => true,
            'periodo'                => $periodo,
            'factura_id'             => $facturaReal->id,
            'suma_iot'               => $sumaIot,
            'total_factura'          => $totalFactura,
            'cantidad_reconciliadas' => $comprasIot->count(),
        ];
    }

    public function reconciliarTodoElAnio(int $proveedorId, int $anio): array
    {
        $resultados = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $resultados[] = $this->reconciliarPeriodo($proveedorId, $anio, $mes);
        }
        return $resultados;
    }
}
