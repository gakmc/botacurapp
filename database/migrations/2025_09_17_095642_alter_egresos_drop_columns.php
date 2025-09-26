<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEgresosDropColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('egresos', function(Blueprint $table){
            if (Schema::hasColumn('egresos', 'fecha')) {
                $table->dropColumn('fecha');
            }
            if (Schema::hasColumn('egresos', 'folio')) {
                $table->dropColumn('folio');
            }
            if (Schema::hasColumn('egresos', 'neto')) {
                $table->dropColumn('neto');
            }
            if (Schema::hasColumn('egresos', 'iva')) {
                $table->dropColumn('iva');
            }
            if (Schema::hasColumn('egresos', 'impuesto_incluido')) {
                $table->dropColumn('impuesto_incluido');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->string('folio')->nullable()->after('total');
            $table->integer('neto')->nullable()->after('folio');
            $table->integer('iva')->nullable()->after('neto');
            $table->integer('impuesto_incluido')->nullable()->after('iva');
            $table->date('fecha')->nullable()->after('subcategoria_id');
        });
    }
}
