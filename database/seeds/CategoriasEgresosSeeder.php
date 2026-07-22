<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CategoriasEgresosSeeder
 *
 * Subcategorías = proveedores reales obtenidos del RCV SII.
 * Hace TRUNCATE de subcategorias_compras y re-siembra con proveedores reales.
 * Las categorías principales (Gastos Fijos / Gastos Variables) se crean si no existen.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class CategoriasEgresosSeeder extends Seeder
{
    public function run()
    {
        // ── 1. Limpiar subcategorías (sin tocar categorías) ──────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('subcategorias_compras')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── 2. Proveedores agrupados por categoría ────────────────────────────
        $categorias = [
            'Gastos Fijos' => [
                'Transbank S.A',
                'Telefónica Móviles Chile S.A.',
            ],
            'Gastos Variables' => [
                // Fallback genérico (se usa cuando el proveedor no tiene match)
                'Otros / Varios',
                // Alimentos y bebidas
                'AGRICOLA INDUSTRIAL LO VALLEDOR AASA S.A.',
                'DISTRIBUIDORA Y COMERCIALIZADORA DE PRODUCTOS ALIMENTICIOS COCHA LTDA',
                'CENCOSUD RETAIL S.A.',
                'DISTRIBUIDORA LA ESTRELLA LIMITADA',
                'SOCIEDAD AVICOLA RIO MAIPO LIMITADA.',
                'RENDIC HERMANOS S.A.',
                'ELABORACIÓN DE CERVEZAS ARTESANALES ECATERINA MUÑOZ EMPRESA INDIVIDU',
                'DULCE ESPIGA SPA',
                'COMERCIAL HIELO FLORIDA SPA',
                'Comercializadora El Mirador SA',
                // Materiales y construcción
                'FERRETERIA Y MATERIALES DE CONSTRUCCION COMERCIO LTDA',
                'MATERIALES DE CONSTRUCCION CR 29:12 LTDA',
                'SODIMAC S.A.',
                'ACTIVIDADES DE DISEÑO Y DECORACION DE INTERIORES CAMILA PAZ FERNANDA W',
                // Honorarios / servicios
                'CLODOMIRA ELIZABETH WIRLOK CHANDIA',
                'JORGE ANTONIO PIERATTINI SANDOVAL',
            ],
        ];

        // ── 3. Insertar ───────────────────────────────────────────────────────
        foreach ($categorias as $nombreCat => $proveedores) {
            $cat = DB::table('categorias_compras')->where('nombre', $nombreCat)->first();

            if (!$cat) {
                $catId = DB::table('categorias_compras')->insertGetId([
                    'nombre'     => $nombreCat,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $catId = $cat->id;
            }

            foreach ($proveedores as $nombre) {
                DB::table('subcategorias_compras')->insert([
                    'nombre'       => $nombre,
                    'categoria_id' => $catId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        $this->command->info('Subcategorías sembradas: ' . DB::table('subcategorias_compras')->count() . ' proveedores SII.');
    }
}
