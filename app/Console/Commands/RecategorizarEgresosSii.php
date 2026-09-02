<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecategorizarEgresosSii extends Command
{
    protected $signature = 'egresos:recategorizar-sii {--dry-run}';
    protected $description = 'Recategoriza los egresos fuente=sii usando el mapeo proveedor.subcategoria_id (backfill del fix de matching por proveedor)';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $egresos = DB::table('egresos as e')
            ->join('proveedores as p', 'p.id', '=', 'e.proveedor_id')
            ->whereNotNull('p.subcategoria_id')
            ->where('e.fuente', 'sii')
            ->select('e.id', 'e.categoria_id', 'e.subcategoria_id', 'p.subcategoria_id as nueva_subcategoria_id', 'p.nombre as proveedor_nombre')
            ->get();

        if ($egresos->isEmpty()) {
            $this->info('No hay egresos SII con proveedor mapeado todavia.');
            return;
        }

        $cambiados = 0;
        $sinCambio = 0;

        foreach ($egresos as $eg) {
            $subCat = DB::table('subcategorias_compras')->where('id', $eg->nueva_subcategoria_id)->first();
            if (!$subCat) {
                continue;
            }
            $nuevaCategoriaId = $subCat->categoria_id;

            if ((int)$eg->categoria_id === (int)$nuevaCategoriaId && (int)$eg->subcategoria_id === (int)$eg->nueva_subcategoria_id) {
                $sinCambio++;
                continue;
            }

            $this->line("egreso #{$eg->id} ({$eg->proveedor_nombre}): categoria_id {$eg->categoria_id}->{$nuevaCategoriaId}, subcategoria_id {$eg->subcategoria_id}->{$eg->nueva_subcategoria_id}");

            if (!$dryRun) {
                DB::table('egresos')->where('id', $eg->id)->update([
                    'categoria_id'    => $nuevaCategoriaId,
                    'subcategoria_id' => $eg->nueva_subcategoria_id,
                ]);
            }
            $cambiados++;
        }

        $accion = $dryRun ? '(dry-run, sin escribir)' : 'actualizados';
        $this->info("Listo. {$cambiados} egresos {$accion}, {$sinCambio} ya estaban correctos.");
    }
}
