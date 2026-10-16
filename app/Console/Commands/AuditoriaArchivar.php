<?php

namespace App\Console\Commands;

use App\Auditoria;
use App\Console\Concerns\ComandoAuditable;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mueve de `auditorias` a `auditorias_archivo` los registros que superaron su
 * retención "en caliente" según la categoría (config/auditoria.php).
 *
 * Solo opera sobre las tablas de auditoría; nunca toca datos del negocio.
 * Cada lote se copia y elimina dentro de una transacción, con insertOrIgnore:
 * si se interrumpe, se puede volver a ejecutar sin duplicar ni perder registros.
 */
class AuditoriaArchivar extends Command
{
    use ComandoAuditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_SEGURIDAD;

    protected $signature = 'auditoria:archivar
                            {--dry-run : Solo muestra cuántos registros se archivarían}
                            {--lote=1000 : Registros por transacción}';

    protected $description = 'Mueve al archivo los registros de auditoría que superaron su retención en caliente';

    public function handle()
    {
        $simulacion = (bool) $this->option('dry-run');
        $lote       = max(100, (int) $this->option('lote'));
        $resumen    = [];
        $total      = 0;

        foreach ((array) config('auditoria.retencion') as $categoria => $plazos) {
            $meses  = (int) $plazos['caliente_meses'];
            $limite = Carbon::now()->subMonths($meses);

            $pendientes = DB::table('auditorias')
                ->where('categoria', $categoria)
                ->where('created_at', '<', $limite);

            $cantidad = (clone $pendientes)->count();

            if ($simulacion || $cantidad === 0) {
                $resumen[] = "{$categoria}: {$cantidad}" . ($simulacion ? ' por archivar' : '') . " (anteriores al {$limite->format('d-m-Y')}, {$meses} meses)";
                continue;
            }

            $movidos = 0;

            while (true) {
                $ids = (clone $pendientes)->orderBy('id')->limit($lote)->pluck('id');
                if ($ids->isEmpty()) {
                    break;
                }

                DB::transaction(function () use ($ids) {
                    $ahora = Carbon::now()->toDateTimeString();

                    $filas = DB::table('auditorias')->whereIn('id', $ids)->get(Auditoria::COLUMNAS_REGISTRO)
                        ->map(function ($fila) use ($ahora) {
                            return (array) $fila + ['archivado_at' => $ahora];
                        })
                        ->all();

                    DB::table('auditorias_archivo')->insertOrIgnore($filas);
                    DB::table('auditorias')->whereIn('id', $ids)->delete();
                });

                $movidos += $ids->count();
            }

            $total    += $movidos;
            $resumen[] = "{$categoria}: {$movidos} archivados (anteriores al {$limite->format('d-m-Y')})";
        }

        foreach ($resumen as $linea) {
            $this->line('  ' . $linea);
        }

        $this->info($simulacion
            ? 'Simulación: no se movió ningún registro. ' . implode(' | ', $resumen)
            : "Archivo completado: {$total} registro(s) movidos. " . implode(' | ', $resumen));

        return 0;
    }
}
