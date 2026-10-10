<?php

namespace App\Console\Commands;

use App\Services\InventarioService;
use Illuminate\Console\Command;

class InventarioConciliar extends Command
{
    protected $signature = 'inventario:conciliar';

    protected $description = 'Revisa las ventas desde la primera toma de inventario y corrige los descuentos de insumos que falten o sobren';

    public function handle(InventarioService $inventario)
    {
        $r = $inventario->conciliarVentas();
        $this->info("Detalles revisados: {$r['detalles']} | Movimientos corregidos: {$r['movimientos']} | Errores: {$r['errores']}");
        return $r['errores'] ? 1 : 0;
    }
}
