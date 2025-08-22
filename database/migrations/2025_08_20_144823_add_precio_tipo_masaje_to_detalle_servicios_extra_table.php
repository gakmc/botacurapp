<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrecioTipoMasajeToDetalleServiciosExtraTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detalle_servicios_extra', function (Blueprint $table) {
            $table->unsignedInteger('id_precio_tipo_masaje')->nullable()->after('id_servicio_extra');
            $table->foreign('id_precio_tipo_masaje', 'fk_detalle_precio_tipo_masaje')->references('id')->on('precios_tipos_masajes')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detalle_servicios_extra', function (Blueprint $table) {
            // eliminar la foreign key
            $table->dropForeign('fk_detalle_precio_tipo_masaje');
            // eliminar la columna
            $table->dropColumn('id_precio_tipo_masaje');
        });
    }
}
