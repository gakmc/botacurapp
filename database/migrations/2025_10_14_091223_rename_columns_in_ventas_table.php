<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnsInVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->renameColumn('imagen_abono', 'folio_abono');
            $table->renameColumn('imagen_diferencia', 'folio_diferencia');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->renameColumn('folio_abono', 'imagen_abono');
            $table->renameColumn('folio_diferencia', 'imagen_diferencia');
        });
    }
}
