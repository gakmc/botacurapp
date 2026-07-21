<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Comando de emergencia para registrar manualmente una instalación de gas.
 *
 * Úsalo cuando la automatización de HA falla (ej: tabla no existe aún).
 *
 * Uso básico:
 *   php artisan gas:registrar --lugar=tinaja_1 --fecha="2026-06-28 09:30:00" --horas=235.4
 *
 * Con datos adicionales:
 *   php artisan gas:registrar --lugar=tinaja_1 --fecha="2026-06-28 09:30:00" --horas=235.4
 *                             --valor=18000 --kg=15 --proveedor="Gasco" --doc="B-123456"
 *
 * Solo consultar historial sin insertar:
 *   php artisan gas:registrar --lugar=tinaja_1 --solo-consultar
 */
class RegistrarInstalacionGas extends Command
{
    protected $signature = 'gas:registrar
                            {--lugar=tinaja_1        : tinaja_1 | tinaja_2 | gas_casa | gas_cocina}
                            {--fecha=                : Fecha y hora (YYYY-MM-DD HH:MM:SS). Default: ahora}
                            {--horas=                : Horas de uso del cilindro anterior (input_number.horas_gas_tinaja_X)}
                            {--valor=                : Valor del cilindro en CLP}
                            {--kg=                   : Kg del cilindro}
                            {--proveedor=            : Nombre del proveedor}
                            {--doc=                  : N° documento / boleta}
                            {--obs=                  : Observación}
                            {--solo-consultar        : Solo muestra historial sin insertar}';

    protected $description = 'Registra manualmente una instalación de cilindro de gas (crea la tabla si no existe)';

    public function handle()
    {
        $lugar        = $this->option('lugar');
        $fechaNueva   = $this->option('fecha') ?: now()->format('Y-m-d H:i:s');
        $horas        = $this->option('horas');
        $soloConsultar = $this->option('solo-consultar');

        $lugaresValidos = ['tinaja_1', 'tinaja_2', 'gas_casa', 'gas_cocina'];
        if (!in_array($lugar, $lugaresValidos)) {
            $this->error("Lugar inválido: {$lugar}. Válidos: " . implode(', ', $lugaresValidos));
            return 1;
        }

        // ── 1. Crear tabla si no existe ──────────────────────────────────────
        $this->crearTablaIfNotExists();

        // ── 2. Consultar historial de este lugar ─────────────────────────────
        $this->mostrarHistorial($lugar);

        if ($soloConsultar) {
            return 0;
        }

        // ── 3. Buscar instalación anterior ───────────────────────────────────
        $anterior = DB::connection('mysql_iot')
            ->table('gas_instalaciones')
            ->where('lugar', $lugar)
            ->where('estado', 'instalado')
            ->orderByDesc('fecha_instalacion')
            ->first();

        $fechaAnterior = $anterior ? $anterior->fecha_instalacion : null;
        $diasDuracion  = null;

        if ($fechaAnterior) {
            $diasDuracion = (int) Carbon::parse($fechaAnterior)->diffInDays(Carbon::parse($fechaNueva));
        }

        // ── 4. Confirmar datos a insertar ────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan>━━━ NUEVO REGISTRO ━━━</>');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Lugar',               $lugar],
                ['Fecha instalación',   $fechaNueva],
                ['Fecha anterior',      $fechaAnterior ?? '— (primer registro)'],
                ['Días duración ant.',  $diasDuracion  ?? '—'],
                ['Horas encendido calefont',($horas !== null && $horas !== '') ? $horas . ' h' : '⚠ No proporcionadas'],
                ['Valor cilindro',      $this->option('valor')    ? '$' . number_format((int)$this->option('valor'), 0, ',', '.') : '—'],
                ['Kg cilindro',         $this->option('kg')       ?? '—'],
                ['Proveedor',           $this->option('proveedor') ?? '—'],
                ['Documento',           $this->option('doc')      ?? '—'],
                ['Observación',         $this->option('obs')      ?? '—'],
                ['Origen',              'home_assistant (registro manual diferido)'],
            ]
        );

        if (!$this->confirm('¿Insertar este registro en gas_instalaciones (BD IoT)?')) {
            $this->warn('Operación cancelada.');
            return 0;
        }

        // ── 5. Insertar ──────────────────────────────────────────────────────
        try {
            $id = DB::connection('mysql_iot')->table('gas_instalaciones')->insertGetId([
                'lugar'                      => $lugar,
                'fecha_instalacion'          => $fechaNueva,
                'fecha_instalacion_anterior' => $fechaAnterior,
                'dias_duracion_anterior'     => $diasDuracion,
                'valor_cilindro_clp'         => $this->option('valor')     ? (int) $this->option('valor')    : null,
                'kg_cilindro'                => $this->option('kg')        ? (float) $this->option('kg')     : null,
                'proveedor_nombre'           => $this->option('proveedor') ?: null,
                'documento'                  => $this->option('doc')       ?: null,
                'observacion'                => $this->option('obs')       ?: 'Registro manual diferido – botón HA ejecutó a las 09:30 pero API falló (tabla gas_instalaciones inexistente). Horas = combustión real calefont (sensor sonoff_10012a25d8_temperature).',
                'gas_compra_id'              => null,
                'egreso_id'                  => null,
                'contador_anterior_valor'    => ($horas !== null && $horas !== '') ? (float) $horas : null,
                'contador_anterior_unidad'   => ($horas !== null && $horas !== '') ? 'horas' : null,
                'origen'                     => 'home_assistant',
                'estado'                     => 'instalado',
                'created_at'                 => now(),
                'updated_at'                 => now(),
            ]);

            $this->newLine();
            $this->info("✅ Registro insertado correctamente (ID #{$id})");

            // ── 6. Resumen horas de uso ──────────────────────────────────────
            if ($horas !== null && $horas !== '') {
                $h = (float) $horas;
                $dias_aprox = round($h / 24, 1);
                $this->newLine();
                $this->line('<fg=green>━━━ HORAS DE USO DEL CILINDRO ANTERIOR ━━━</>');
                $this->table(
                    ['Métrica', 'Valor'],
                    [
                        ['Horas encendido calefont', number_format($h, 1) . ' h'],
                        ['(≈ combustión real de gas)', 'Detectado por variación temp. sensor sonoff_10012a25d8'],
                        ['Equivalente en días',        $dias_aprox . ' días de calefont encendido'],
                        ['Días calendario (por fecha)', $diasDuracion !== null ? $diasDuracion . ' días' : '— (primer registro)'],
                    ]
                );
            } else {
                $this->newLine();
                $this->warn('⚠  No se proporcionaron horas de encendido del calefont.');
                $this->line('');
                $this->line('   Para recuperarlas, busca en el historial de HA:');
                $this->line('   → Herramientas para desarrolladores → Historial');
                $this->line('   → Entidad: input_number.horas_gas_tinaja_1');
                $this->line('   → Fecha: hoy 28-06-2026');
                $this->line('   → Busca el último valor ANTES de las 09:30 (antes del reset automático a 0)');
                $this->line('');
                $this->line('   Este contador mide horas de COMBUSTIÓN REAL del calefont,');
                $this->line('   detectado por variación de temperatura en sensor.sonoff_10012a25d8_temperature.');
                $this->line('');
                $this->line('   Una vez tengas el valor, actualiza con:');
                $this->line('   <fg=cyan>php artisan gas:actualizar-horas --id=' . $id . ' --horas=XXX</>');
            }

        } catch (\Throwable $e) {
            $this->error('Error al insertar: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function crearTablaIfNotExists(): void
    {
        $existe = DB::connection('mysql_iot')
            ->select("SHOW TABLES LIKE 'gas_instalaciones'");

        if (!empty($existe)) {
            $this->line('<fg=green>✓ Tabla gas_instalaciones ya existe.</>');
            return;
        }

        $this->warn('⚠  Tabla gas_instalaciones no existe. Creando...');

        DB::connection('mysql_iot')->statement("
            CREATE TABLE `gas_instalaciones` (
                `id`                         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `lugar`                      ENUM('tinaja_1','tinaja_2','gas_casa','gas_cocina') NOT NULL,
                `fecha_instalacion`          DATETIME NOT NULL,
                `fecha_instalacion_anterior` DATETIME NULL,
                `dias_duracion_anterior`     INT NULL COMMENT 'Días que duró el cilindro anterior',
                `valor_cilindro_clp`         INT NULL,
                `kg_cilindro`                DECIMAL(5,2) NULL,
                `proveedor_nombre`           VARCHAR(150) NULL,
                `documento`                  VARCHAR(120) NULL,
                `gas_compra_id`              INT UNSIGNED NULL COMMENT 'ID en gas_compras (BD principal)',
                `egreso_id`                  INT UNSIGNED NULL COMMENT 'ID en egresos (BD principal)',
                `contador_anterior_valor`    DECIMAL(10,2) NULL,
                `contador_anterior_unidad`   VARCHAR(30) NULL COMMENT 'm3 | kg | bar | horas',
                `origen`                     ENUM('home_assistant','manual') NOT NULL DEFAULT 'home_assistant',
                `estado`                     ENUM('instalado','anulado') NOT NULL DEFAULT 'instalado',
                `observacion`                TEXT NULL,
                `created_at`                 TIMESTAMP NULL,
                `updated_at`                 TIMESTAMP NULL,
                INDEX `idx_lugar_fecha` (`lugar`, `fecha_instalacion`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->info('✅ Tabla gas_instalaciones creada correctamente.');
    }

    private function mostrarHistorial(string $lugar): void
    {
        $registros = DB::connection('mysql_iot')
            ->table('gas_instalaciones')
            ->where('lugar', $lugar)
            ->orderByDesc('fecha_instalacion')
            ->limit(5)
            ->get();

        $this->newLine();
        $this->line("<fg=cyan>━━━ HISTORIAL: {$lugar} (últimos 5 registros) ━━━</>");

        if ($registros->isEmpty()) {
            $this->warn('   Sin registros previos – este será el primero.');
            return;
        }

        $rows = $registros->map(function ($r) {
            return [
                '#'          => $r->id,
                'Fecha inst.'=> $r->fecha_instalacion,
                'Fecha ant.' => $r->fecha_instalacion_anterior ?? '-',
                'Dias dur.'  => $r->dias_duracion_anterior ?? '-',
                'Horas uso'  => $r->contador_anterior_valor ? $r->contador_anterior_valor . ' h' : '-',
                'Estado'     => $r->estado,
            ];
        })->toArray();

        $this->table(
            ['#', 'Fecha inst.', 'Fecha ant.', 'Días dur.', 'Horas uso', 'Estado'],
            $rows
        );
    }
}
