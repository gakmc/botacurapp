<?php

namespace App\Console\Commands;

use App\Asignacion;
use App\Propina;
use App\Sueldo;
use Illuminate\Console\Command;

/**
 * Corrección puntual: el 2026-08-13 se ingresó por error el turno de
 * Sebastián Wimmer (user_id=1). Se debe:
 *   1) Eliminar su registro de sueldo de ese día.
 *   2) Sacarlo de la asignación (turno) de ese día.
 *   3) En cada propina de ese día donde estaba incluido, sacarlo y
 *      repartir el monto total de la propina en partes iguales entre
 *      los que sí trabajaron ese día.
 *
 * Uso: php artisan sueldos:corregir-turno-13agosto --dry-run
 */
class CorregirTurno13Agosto extends Command
{
    protected $signature = 'sueldos:corregir-turno-13agosto {--dry-run}';

    protected $description = 'Corrige el turno del 2026-08-13 ingresado por error para Sebastián Wimmer (user_id=1)';

    const FECHA   = '2026-08-13';
    const USER_ID = 1;

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'MODO DRY-RUN: no se escribe nada.' : 'APLICANDO CORRECCIÓN...');
        $this->line('');

        // 1) Sueldo del día
        $sueldo = Sueldo::where('id_user', self::USER_ID)
            ->whereDate('dia_trabajado', self::FECHA)
            ->first();

        if ($sueldo) {
            $this->line("Sueldo a eliminar: #{$sueldo->id} | valor_dia={$sueldo->valor_dia} | sub_sueldo={$sueldo->sub_sueldo} | total_pagar={$sueldo->total_pagar}");
            if (!$dryRun) {
                $sueldo->delete();
            }
        } else {
            $this->warn('No se encontró sueldo para ese usuario/fecha.');
        }

        // 2) Asignación (turno) del día
        $asignacion = Asignacion::whereDate('fecha', self::FECHA)->first();
        if ($asignacion) {
            $estabaAsignado = $asignacion->users()->where('users.id', self::USER_ID)->exists();
            $this->line("Asignación #{$asignacion->id} — usuario " . ($estabaAsignado ? 'SÍ' : 'NO') . ' estaba asignado.');
            if ($estabaAsignado && !$dryRun) {
                $asignacion->users()->detach(self::USER_ID);
            }
        } else {
            $this->warn('No se encontró asignación para esa fecha.');
        }

        // 3) Propinas del día
        $this->line('');
        $propinas = Propina::whereDate('fecha', self::FECHA)->with('users')->get();

        foreach ($propinas as $propina) {
            $estaba = $propina->users->firstWhere('id', self::USER_ID);
            if (!$estaba) {
                continue;
            }

            $restantes = $propina->users->reject(function ($u) {
                return $u->id === self::USER_ID;
            });

            if ($restantes->isEmpty()) {
                $this->warn("Propina #{$propina->id}: no quedan usuarios para repartir, se omite.");
                continue;
            }

            $nuevoMonto = round(((float) $propina->cantidad) / $restantes->count(), 2);

            $this->line("Propina #{$propina->id} | cantidad={$propina->cantidad} | antes: " . ($restantes->count() + 1) . ' personas a $' . number_format($estaba->pivot->monto_asignado, 2) . ' c/u');
            $this->line("  -> después: {$restantes->count()} personas a \${$nuevoMonto} c/u (se saca a Sebastián)");

            if (!$dryRun) {
                $propina->users()->detach(self::USER_ID);
                foreach ($restantes as $u) {
                    $propina->users()->updateExistingPivot($u->id, ['monto_asignado' => $nuevoMonto]);
                }
            }
        }

        $this->line('');
        $this->info($dryRun ? 'Dry-run completo. Corré sin --dry-run para aplicar.' : 'Corrección aplicada.');
    }
}
