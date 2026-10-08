<?php

namespace App\Console\Commands;

use App\Services\BotAprendizajeService;
use Illuminate\Console\Command;

/**
 * php artisan bot:aprender [--dias=7]
 * Analiza las conversaciones recientes con IA y deja sugerencias en
 * Configuración del bot → Aprendizajes (nada se aplica sin aprobación).
 * Programado a diario a las 23:40 en app/Console/Kernel.php.
 */
class BotAprender extends Command
{
    protected $signature = 'bot:aprender {--dias=7 : Días hacia atrás a revisar}';
    protected $description = 'Analiza conversaciones del bot y propone aprendizajes';

    public function handle()
    {
        $r = BotAprendizajeService::analizar(max(1, min(60, (int) $this->option('dias'))));
        $this->line($r['mensaje']);
        return $r['ok'] ? 0 : 1;
    }
}
