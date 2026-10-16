<?php

namespace App\Console\Commands;

use App\HonorarioBte;
use App\Proveedor;
use App\Services\SiiBteService;
use App\Auditoria;
use App\Console\Concerns\ComandoAuditable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SincronizarHonorariosBhe extends Command
{
    use ComandoAuditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $signature = 'honorarios:sync
                            {anio? : Año (default: año actual)}
                            {--mes= : Mes específico 1-12}
                            {--forzar : Re-sincroniza aunque ya existan registros}';

    protected $description = 'Sincroniza BHE desde SII (login único para todos los meses)';

    private $bte;

    public function __construct(SiiBteService $bte)
    {
        parent::__construct();
        $this->bte = $bte;
    }

    public function handle()
    {
        if (!$this->bte->credencialesConfiguradas()) {
            $this->error('Credenciales SII no configuradas en .env');
            return 1;
        }

        $anio   = (int) ($this->argument('anio') ?: now()->year);
        $mesOpc = $this->option('mes') ? (int) $this->option('mes') : null;
        $forzar = $this->option('forzar');
        $mesMax = ($anio === (int) now()->year) ? (int) now()->month : 12;

        $todosLosMeses   = $mesOpc ? [$mesOpc] : range(1, $mesMax);
        $mesesPendientes = [];

        foreach ($todosLosMeses as $mes) {
            $periodo = sprintf('%04d%02d', $anio, $mes);
            if (!$forzar && HonorarioBte::where('periodo', $periodo)->exists()) {
                $count = HonorarioBte::where('periodo', $periodo)->count();
                $this->line("  {$periodo}: ya sincronizado ({$count} registros). Usar --forzar para re-sync.");
            } else {
                $mesesPendientes[] = $mes;
            }
        }

        if (empty($mesesPendientes)) {
            $this->info('Nada por sincronizar.');
            return 0;
        }

        $this->info("Sincronizando BHE {$anio} — " . count($mesesPendientes) . " mes(es) — login único SII...");
        $this->line('');

        $resultados = $this->bte->consultarMultiplesMeses($anio, $mesesPendientes, 3);

        $totalIns = 0;
        $totalAct = 0;
        $errores  = [];

        foreach ($resultados as $periodo => $r) {
            if (!$r['ok']) {
                $this->error("  {$periodo}: ERROR — {$r['error']}");
                $errores[] = $periodo;
                continue;
            }

            $btes  = $r['data'];
            $ahora = now();
            $ins   = 0;
            $act   = 0;

            DB::beginTransaction();
            try {
                foreach ($btes as $bhe) {
                    $existe = HonorarioBte::where('folio', $bhe['folio'])
                        ->where('rut_emisor', $bhe['rut_emisor'])
                        ->first();
                    if ($existe) {
                        $existe->update(array_merge($bhe, ['sincronizado_at' => $ahora]));
                        $act++;
                    } else {
                        $provId    = null;
                        $proveedor = Proveedor::where('rut', $bhe['rut_emisor'])->first();
                        if ($proveedor) {
                            $provId = $proveedor->id;
                        }
                        HonorarioBte::create(array_merge($bhe, [
                            'proveedor_id'    => $provId,
                            'sincronizado_at' => $ahora,
                        ]));
                        $ins++;
                    }
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("  {$periodo}: ERROR DB — {$e->getMessage()}");
                $errores[] = $periodo;
                continue;
            }

            if (count($btes) === 0) {
                $this->line("  {$periodo}: sin movimientos.");
            } else {
                $bruto = array_sum(array_column($btes, 'monto_bruto'));
                $this->line("  {$periodo}: " . count($btes) . " BHE — \$" . number_format($bruto, 0, ',', '.') . " | +{$ins} nuevas, ~{$act} act.");
            }

            $totalIns += $ins;
            $totalAct += $act;
        }

        $this->line('');
        $this->info("Listo. +{$totalIns} insertadas, ~{$totalAct} actualizadas.");

        if (!empty($errores)) {
            $this->warn('Errores en: ' . implode(', ', $errores));
            return 1;
        }
        return 0;
    }
}
