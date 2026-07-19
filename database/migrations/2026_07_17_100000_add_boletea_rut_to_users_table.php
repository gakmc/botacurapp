<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a la tabla users los campos necesarios para el módulo de honorarios:
 *
 *   boletea  → true si el funcionario emite Boleta de Honorarios Electrónica (BTE)
 *   rut      → RUT del trabajador (sin puntos, con guión) para vincular con honorarios_bte
 *
 * Los que tienen boletea=true tienen retención (15.25% año 2026) sobre su sueldo base.
 * Los que tienen boletea=false no tienen descuento.
 */
class AddBoleteaRutToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rut', 15)->nullable()->after('name')
                  ->comment('RUT del trabajador (ej: 21073497-K) para vincular con BTE');
            $table->boolean('boletea')->default(false)->after('rut')
                  ->comment('True si emite Boleta de Honorarios Electrónica');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rut', 'boletea']);
        });
    }
}
