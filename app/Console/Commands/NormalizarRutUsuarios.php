<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

/**
 * Corrige RUTs guardados con formato inconsistente (doble guión, sin
 * guión, con puntos, etc) dejándolos todos como "cuerpo-DV" con un
 * único guión. Ej: "13465824--K" -> "13465824-K".
 *
 * Uso: php artisan usuarios:normalizar-rut --dry-run
 */
class NormalizarRutUsuarios extends Command
{
    protected $signature = 'usuarios:normalizar-rut {--dry-run}';
    protected $description = 'Normaliza el formato de users.rut a cuerpo-DV con un solo guión';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $usuarios = User::whereNotNull('rut')->where('rut', '!=', '')->get();

        $cambios = 0;

        foreach ($usuarios as $user) {
            $original = $user->rut;
            $limpio   = strtoupper(str_replace(['.', ' ', '-'], '', $original));

            if (strlen($limpio) < 2) {
                continue;
            }

            $dv       = substr($limpio, -1);
            $cuerpo   = substr($limpio, 0, -1);
            $corregido = "{$cuerpo}-{$dv}";

            if ($corregido !== $original) {
                $cambios++;
                $this->line("  {$user->name} (id {$user->id}): '{$original}' -> '{$corregido}'");

                if (!$dryRun) {
                    $user->rut = $corregido;
                    $user->save();
                }
            }
        }

        if ($cambios === 0) {
            $this->info('Todos los RUT ya estaban bien formateados.');
            return;
        }

        $this->info($dryRun
            ? "\n{$cambios} RUT(s) se corregirían. Corré sin --dry-run para aplicar."
            : "\n{$cambios} RUT(s) corregidos.");
    }
}
