<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega "Otros / Varios" como primera subcategoría de "Gastos Variables".
 * Se usa como fallback genérico cuando el proveedor no tiene auto-match.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class AddOtrosSubcategoriaGastosVariables extends Migration
{
    public function up()
    {
        $cat = DB::table('categorias_compras')->where('nombre', 'Gastos Variables')->first();

        if (!$cat) {
            return; // Sin la categoría no hay nada que hacer
        }

        $yaExiste = DB::table('subcategorias_compras')
            ->where('categoria_id', $cat->id)
            ->where('nombre', 'Otros / Varios')
            ->exists();

        if ($yaExiste) {
            return;
        }

        DB::table('subcategorias_compras')->insert([
            'nombre'       => 'Otros / Varios',
            'categoria_id' => $cat->id,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down()
    {
        $cat = DB::table('categorias_compras')->where('nombre', 'Gastos Variables')->first();

        if ($cat) {
            DB::table('subcategorias_compras')
                ->where('categoria_id', $cat->id)
                ->where('nombre', 'Otros / Varios')
                ->delete();
        }
    }
}
