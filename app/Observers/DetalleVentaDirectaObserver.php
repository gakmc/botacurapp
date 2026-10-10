<?php

namespace App\Observers;

use App\DetalleVentaDirecta;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Log;

/**
 * Descuenta insumos según receta en ventas directas. Las eliminaciones en
 * bloque (VentaDirecta::deleting) no disparan este observer: las corrige
 * inventario:conciliar.
 */
class DetalleVentaDirectaObserver
{
    public function saved(DetalleVentaDirecta $d)
    {
        $this->sync($d, (int) $d->cantidad);
    }

    public function deleted(DetalleVentaDirecta $d)
    {
        $this->sync($d, 0);
    }

    private function sync(DetalleVentaDirecta $d, $cantidad)
    {
        try {
            app(InventarioService::class)->sincronizarVenta(DetalleVentaDirecta::class, $d->id, $d->producto_id, $cantidad, $d->created_at, 'venta_directa');
        } catch (\Throwable $e) {
            Log::error('Inventario: no se pudo descontar venta directa', ['detalle' => $d->id, 'error' => $e->getMessage()]);
        }
    }
}
