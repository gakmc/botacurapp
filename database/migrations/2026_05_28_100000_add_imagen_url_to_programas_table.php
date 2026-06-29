<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImagenUrlToProgramasTable extends Migration
{
    /**
     * Agrega columna imagen_url a programas para almacenar
     * la URL de la infografía que el bot envía al cliente
     * cuando selecciona un programa.
     */
    public function up()
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->string('imagen_url')->nullable()->after('espacio_tipo')
                  ->comment('URL pública de la infografía del programa (WhatsApp media)');
        });
    }

    public function down()
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->dropColumn('imagen_url');
        });
    }
}
