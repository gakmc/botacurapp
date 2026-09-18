<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * MenuProductosSeeder
 *
 * Siembra el catálogo de platos del Menú Otoño 2026 en las tablas:
 *   sectores → tipos_productos → productos
 *
 * Es idempotente: solo inserta si no existe (usa firstOrCreate por nombre).
 * Compatible Laravel 6 / PHP 7.2
 */
class MenuProductosSeeder extends Seeder
{
    public function run()
    {
        // ── 1. Sector "Cocina" ─────────────────────────────────────────────────
        $sectorId = DB::table('sectores')
            ->where('nombre', 'Cocina')
            ->value('id');

        if (!$sectorId) {
            $sectorId = DB::table('sectores')->insertGetId([
                'nombre'     => 'Cocina',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 2. Tipos de producto ───────────────────────────────────────────────
        $tipos = ['entrada', 'fondo', 'acompañamiento'];
        $tipoIds = [];

        foreach ($tipos as $tipo) {
            $id = DB::table('tipos_productos')
                ->where('nombre', $tipo)
                ->where('id_sector', $sectorId)
                ->value('id');

            if (!$id) {
                $id = DB::table('tipos_productos')->insertGetId([
                    'nombre'     => $tipo,
                    'id_sector'  => $sectorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $tipoIds[$tipo] = $id;
        }

        // ── 3. Productos ───────────────────────────────────────────────────────
        $catalogo = [
            'entrada' => [
                'Crema de Zapallo',
                'Ensalada Repollo Zanahoria',
                'Ensalada Chilena',
                'Ensalada Tomate',
                'Ensalada Surtida',
            ],
            'fondo' => [
                'Costillar al Horno',
                'Carne Mechada',
                'Pollo a la Mostaza',
                'Pastel de Papas',
                'Fettuchinni con Salsa de Champiñón',
                'Fettuchinni con Salsa de Champiñón Veggie',
                'Lasagna de Berenjena con Bechamel Vegana',
            ],
            'acompañamiento' => [
                'Puré Verde',
                'Arroz a la Primavera de la Casa',
                'Papas Mayo',
                'Quinoa',
            ],
        ];

        foreach ($catalogo as $tipo => $nombres) {
            $tipoId = $tipoIds[$tipo];
            foreach ($nombres as $nombre) {
                $existe = DB::table('productos')
                    ->where('nombre', $nombre)
                    ->where('id_tipo_producto', $tipoId)
                    ->exists();

                if (!$existe) {
                    DB::table('productos')->insert([
                        'nombre'           => $nombre,
                        'valor'            => 0,
                        'estado'           => 'activo',
                        'id_tipo_producto' => $tipoId,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }
        }

        $this->command->info('MenuProductosSeeder: OK — ' .
            count($catalogo['entrada']) . ' entradas, ' .
            count($catalogo['fondo']) . ' fondos, ' .
            count($catalogo['acompañamiento']) . ' acompañamientos.'
        );
    }
}
