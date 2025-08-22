<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class AddSlugToServiciosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->string('slug')->nullable();
        });



        // Poblar slugs existentes (evitar colisiones)
        $servicios = DB::table('servicios')->select('id', 'nombre_servicio')->get();
        foreach ($servicios as $s) {
            $base = Str::slug($s->nombre_servicio);
            if ($base === '') {$base = 'servicio-' . $s->id;}

            $slug = $base;
            $i    = 1;
            while (DB::table('servicios')->where('slug', $slug)->where('id', '!=', $s->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            DB::table('servicios')->where('id', $s->id)->update(['slug' => $slug]);
        }

        // Índice único con nombre explícito (para drop limpio en down)
        Schema::table('servicios', function (Blueprint $table) {
            $table->unique('slug', 'servicios_slug_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropUnique('servicios_slug_unique');
            $table->dropColumn('slug');
        });

    }
}
