<?php

namespace App\Console\Commands;

use App\Auditoria;
use App\Console\Concerns\ComandoAuditable;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Elimina definitivamente los registros de auditoría que superaron su plazo
 * total de retención según la categoría (config/auditoria.php: archivo_anios).
 *
 * SOLO opera sobre las tablas `auditorias_archivo` y `auditorias`; nunca toca
 * reservas, ventas, usuarios ni ningún otro dato del negocio.
 */
class AuditoriaPurgar extends Command
{
    use ComandoAuditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_SEGURIDAD;

    protected $signature = 'auditoria:purgar
                            {--dry-run : Solo muestra cuántos registros se eliminarían}
                            {--lote=5000 : Registros por sentencia DELETE}';

    protected $description = 'Elimina los registros de auditoría que superaron su plazo total de retención';

    /** Únicas tablas sobre las que puede operar este comando. */
    const TABLAS = ['auditorias_archivo', 'auditorias'];

    public function handle()
    {
        $simulacion = (bool) $this->option('dry-run');
        $lote       = max(100, (int) $this->option('lote'));
        $resumen    = [];
        $total      = 0;

        foreach ((array) config('auditoria.retencion') as $categoria => $plazos) {
            $anios  = (int) $plazos['archivo_anios'];
            $limite = Carbon::now()->subYears($anios);
            $porCategoria = 0;

            // Normalmente todo está en el archivo; se revisa también la tabla en
            // caliente por si el archivado no se ha ejecutado.
            foreach (self::TABLAS as $tabla) {
                $vencidos = function () use ($tabla, $categoria, $limite) {
                    return DB::table($tabla)->where('categoria', $categoria)->where('created_at', '<', $limite);
                };

                if ($simulacion) {
                    $porCategoria += $vencidos()->count();
                    continue;
                }

                do {
                    $eliminados    = $vencidos()->orderBy('id')->limit($lote)->delete();
                    $porCategoria += $eliminados;
                } while ($eliminados > 0);
            }

            $total    += $porCategoria;
            $resumen[] = "{$categoria}: {$porCategoria}" . ($simulacion ? ' por eliminar' : ' eliminados')
                . " (anteriores al {$limite->format('d-m-Y')}, {$anios} años)";
        }

        foreach ($resumen as $linea) {
            $this->line('  ' . $linea);
        }

        $this->info($simulacion
            ? 'Simulación: no se eliminó ningún registro. ' . implode(' | ', $resumen)
            : "Purga completada: {$total} registro(s) eliminados. " . implode(' | ', $resumen));

        return 0;
    }
}
