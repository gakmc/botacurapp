<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * php artisan db:pull-from-prod [--months=12]
 *
 * Copia las tablas de ingresos/egresos desde producción al local.
 * Requiere que DB_PROD_* esté configurado en .env y que el servidor
 * de producción permita conexiones MySQL remotas desde esta IP.
 *
 * Compatible PHP 7.2 / Laravel 6 (sin arrow fn, sin nullsafe).
 */
class PullFromProd extends Command
{
    protected $signature   = 'db:pull-from-prod
                                {--months=14 : Meses de historial a importar (desde hoy hacia atrás)}
                                {--table=    : Copiar solo esta tabla}';

    protected $description = 'Copia datos de ventas/ingresos desde producción a la base local';

    /**
     * Orden de copia: padres antes que hijos (FK deps).
     * El comando deshabilita FK_CHECKS, así que el orden es solo informativo.
     */
    private $tablas = [
        // Maestros (necesarios para que las FKs de las tablas hijas no rompan en runtime)
        'programas',
        'tipos_transacciones',
        'users',
        // Datos de ingresos
        'reservas',
        'ventas',
        'consumos',
        'detalles_consumos',
        'detalle_servicios_extra',
        'ventas_directas',
        'poro_poro_ventas',
        // Datos de egresos SII / BTE
        'honorarios_bte',
        'egresos',
        'sii_resumen_mensual',
    ];

    public function handle()
    {
        $meses = (int) $this->option('months');
        $soloTabla = $this->option('table');
        $desde = now()->subMonths($meses)->startOfMonth()->toDateString();

        $this->line('');
        $this->info('══════════════════════════════════════════════');
        $this->info('  db:pull-from-prod');
        $this->info("  Desde: {$desde} ({$meses} meses)");
        $this->info('══════════════════════════════════════════════');
        $this->line('');

        // ── Probar conexión a producción ─────────────────────────────────────
        $this->line('Probando conexión a producción...');
        try {
            DB::connection('mysql_prod')->statement('SELECT 1');
            $host = config('database.connections.mysql_prod.host');
            $db   = config('database.connections.mysql_prod.database');
            $this->info("✓ Conectado a {$host} / {$db}");
        } catch (\Exception $e) {
            $this->error('✗ No se puede conectar a producción: ' . $e->getMessage());
            $this->line('');
            $this->warn('── Alternativa: importar con phpMyAdmin ──────────────────────────');
            $this->warn('1. Entra a cPanel → phpMyAdmin → cbo56863_botacurapp');
            $this->warn('2. Selecciona las tablas: reservas, ventas, consumos,');
            $this->warn('   detalles_consumos, detalle_servicios_extra, ventas_directas,');
            $this->warn('   poro_poro_ventas, honorarios_bte, egresos');
            $this->warn('3. Exportar → SQL → Ejecutar');
            $this->warn('4. En phpMyAdmin local (pruebas_botacura): Importar ese .sql');
            $this->line('─────────────────────────────────────────────────────────────────');
            return 1;
        }

        $prod  = DB::connection('mysql_prod');
        $local = DB::connection('mysql');

        // ── Deshabilitar FK checks en local ──────────────────────────────────
        $local->statement('SET FOREIGN_KEY_CHECKS=0');
        $local->statement('SET SESSION sql_mode=""');

        $tablasCopiar = $soloTabla ? [$soloTabla] : $this->tablas;
        $totalReg = 0;
        $errores  = 0;

        foreach ($tablasCopiar as $tabla) {
            $res = $this->importarTabla($prod, $local, $tabla, $desde);
            if ($res >= 0) {
                $totalReg += $res;
            } else {
                $errores++;
            }
        }

        // ── Rehabilitar FK checks ─────────────────────────────────────────────
        $local->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->line('');
        $this->info('══════════════════════════════════════════════');
        $this->info("  ✓ Sincronización completa: {$totalReg} registros");
        if ($errores > 0) {
            $this->warn("  ✗ {$errores} tabla(s) con errores (ver arriba)");
        }
        $this->info('══════════════════════════════════════════════');
        $this->line('');

        return 0;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function importarTabla($prod, $local, $tabla, $desde)
    {
        $this->line("  → {$tabla} ...");

        // Verificar que la tabla existe en local
        try {
            $localCount = $local->table($tabla)->count();
        } catch (\Exception $e) {
            $this->warn("    ✗ No existe en local (¿migraciones pendientes?): " . $e->getMessage());
            return -1;
        }

        // Verificar que la tabla existe en producción
        try {
            $filas = $this->fetchDesdeProduccion($prod, $tabla, $desde);
        } catch (\Exception $e) {
            $this->warn("    ✗ Error leyendo de producción: " . $e->getMessage());
            return -1;
        }

        if (empty($filas)) {
            $this->line("    (sin registros en el período)");
            return 0;
        }

        // Truncar local e insertar
        try {
            $local->table($tabla)->truncate();

            // Insertar en lotes para no sobrecargar memoria
            $lotes = array_chunk($filas, 300);
            foreach ($lotes as $lote) {
                $local->table($tabla)->insert($lote);
            }

            $this->info("    ✓ " . count($filas) . " registros");
            return count($filas);

        } catch (\Exception $e) {
            $this->warn("    ✗ Error insertando: " . $e->getMessage());
            return -1;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function fetchDesdeProduccion($prod, $tabla, $desde)
    {
        $q = $prod->table($tabla);

        // Maestros: sin filtro de fecha — traer completo
        $sinFiltro = ['programas', 'tipos_transacciones', 'users', 'sii_resumen_mensual'];
        if (in_array($tabla, $sinFiltro)) {
            $filas = $q->get();
            return $filas->map(function ($row) { return (array) $row; })->toArray();
        }

        // Tablas con columna 'periodo' formato YYYYMM
        if ($tabla === 'honorarios_bte') {
            $periodoMin = now()->subMonths(14)->format('Ym'); // ej: 202505
            $q->where('periodo', '>=', $periodoMin);
            $filas = $q->get();
            return $filas->map(function ($row) { return (array) $row; })->toArray();
        }

        // Tablas con 'periodo_sii' formato YYYY-MM
        if ($tabla === 'egresos') {
            $periodoMin = now()->subMonths(14)->format('Y-m'); // ej: 2025-05
            $q->where(function ($inner) use ($periodoMin) {
                $inner->where('periodo_sii', '>=', $periodoMin)
                      ->orWhereNull('periodo_sii');
            });
            // También por created_at como fallback
            $q->orWhere('created_at', '>=', $desde);
            // Reiniciar query limpio
            $q = DB::connection('mysql_prod')->table($tabla);
            $q->where(function ($inner) use ($periodoMin, $desde) {
                $inner->where('periodo_sii', '>=', $periodoMin)
                      ->orWhere('created_at', '>=', $desde)
                      ->orWhereNull('periodo_sii');
            });
            $filas = $q->get();
            return $filas->map(function ($row) { return (array) $row; })->toArray();
        }

        // reservas: filtrar por fecha_visita
        if ($tabla === 'reservas') {
            $q->where('fecha_visita', '>=', $desde);
            $filas = $q->get();
            return $filas->map(function ($row) { return (array) $row; })->toArray();
        }

        // ventas_directas y poro_poro_ventas: filtrar por fecha
        if (in_array($tabla, ['ventas_directas', 'poro_poro_ventas'])) {
            $q->where('fecha', '>=', $desde);
            $filas = $q->get();
            return $filas->map(function ($row) { return (array) $row; })->toArray();
        }

        // Resto (ventas, consumos, detalles_consumos, detalle_servicios_extra):
        // filtrar por created_at
        $q->where('created_at', '>=', $desde);
        $filas = $q->get();
        return $filas->map(function ($row) { return (array) $row; })->toArray();
    }
}
