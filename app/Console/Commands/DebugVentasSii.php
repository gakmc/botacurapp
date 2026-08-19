<?php

namespace App\Console\Commands;

use App\Services\SiiService;
use Illuminate\Console\Command;

/**
 * Muestra la respuesta CRUDA del RCV de ventas para un período, sin
 * normalizar, para poder ver los nombres reales de los campos que
 * manda la API (necesario porque el parseo actual está devolviendo
 * $0 en los montos aunque sí encuentra documentos).
 *
 * Uso: php artisan sii:debug-ventas --anio=2026 --mes=1
 */
class DebugVentasSii extends Command
{
    protected $signature = 'sii:debug-ventas {--anio=} {--mes=}';
    protected $description = 'Muestra la respuesta cruda del RCV de ventas (sin normalizar) para diagnosticar el parseo';

    public function handle(SiiService $sii)
    {
        $anio = (int) ($this->option('anio') ?: now()->year);
        $mes  = (int) ($this->option('mes') ?: now()->month);

        if (!$sii->credencialesConfiguradas()) {
            $this->error('Credenciales SII no configuradas.');
            return 1;
        }

        $resultado = $sii->debugVentasResumenCrudo($anio, $mes);

        if (!$resultado['ok']) {
            $this->error('Error: ' . $resultado['error']);
            return 1;
        }

        $this->info("Respuesta cruda para {$anio}-{$mes}:");
        $this->line(json_encode($resultado['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return 0;
    }
}
