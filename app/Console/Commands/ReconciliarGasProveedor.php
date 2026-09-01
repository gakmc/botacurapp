<?php

namespace App\Console\Commands;

use App\Services\GasReconciliacionService;
use Illuminate\Console\Command;

class ReconciliarGasProveedor extends Command
{
    protected $signature = 'egresos:reconciliar-gas {--proveedor=65} {--anio=} {--mes=}';
    protected $description = 'Reconcilia las compras de gas via IoT contra la factura real del proveedor (SII) del mismo periodo';

    public function handle(GasReconciliacionService $service)
    {
        $proveedorId = (int) $this->option('proveedor');
        $anio        = (int) ($this->option('anio') ?: now()->year);
        $mesOption   = $this->option('mes');

        if ($mesOption) {
            $this->mostrarResultado($service->reconciliarPeriodo($proveedorId, $anio, (int) $mesOption));
        } else {
            foreach ($service->reconciliarTodoElAnio($proveedorId, $anio) as $resultado) {
                $this->mostrarResultado($resultado);
            }
        }
    }

    private function mostrarResultado(array $r)
    {
        if ($r['ok']) {
            $this->info("[{$r['periodo']}] OK: {$r['cantidad_reconciliadas']} compras IoT reconciliadas contra factura #{$r['factura_id']} (IoT: \${$r['suma_iot']} vs Factura: \${$r['total_factura']})");
        } elseif ($r['motivo'] === 'diferencia_fuera_de_tolerancia') {
            $this->warn("[{$r['periodo']}] DIFERENCIA ALTA ({$r['porcentaje_diferencia']}%): IoT=\${$r['suma_iot']} vs Factura=\${$r['total_factura']} — revisar manualmente, NO reconciliado");
        }
        // sin_factura_sii / sin_compras_iot: silencioso, es el caso normal para la mayoria de meses/proveedores
    }
}
