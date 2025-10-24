<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPagoMasoterapeutaOnPreciosTiposMasajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('precios_tipos_masajes', function (Blueprint $table) {
            $table->integer('pago_masoterapeuta')->after('precio_pareja')->nullable()->comment('Pago al masoterapeuta por masaje');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('precios_tipos_masajes', function (Blueprint $table) {
            $table->dropColumn('pago_masoterapeuta');
        });
    }
}
