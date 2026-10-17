<?php

namespace App\Observers;

use App\DetalleConsumo;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Log;

/**
 * Descuenta insumos según receta cuando se vende un producto en un consumo.
 * Nunca interrumpe la venta: si algo falla queda en el log y lo corrige
 * inventario:conciliar.
 */
class DetalleConsumoObserver
{
    public function saved(DetalleConsumo $d)
    {
        $this->sync($d, (int) $d->cantidad_producto);
    }

    public function deleted(DetalleConsumo $d)
    {
        $this->sync($d, 0);
    }

    private function sync(DetalleConsumo $d, $cantidad)
    {
        try {
            app(InventarioService::class)->sincronizarVenta(DetalleConsumo::class, $d->id, $d->id_producto, $cantidad, $d->created_at, 'consumo');
        } catch (\Throwable $e) {
            Log::error('Inventario: no se pudo descontar consumo', ['detalle' => $d->id, 'error' => $e->getMessage()]);
        }
    }
}
