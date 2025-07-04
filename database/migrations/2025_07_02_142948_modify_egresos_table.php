<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyEgresosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->unsignedInteger('tipo_documento_id')->nullable()->after('id');
            $table->unsignedInteger('subcategoria_id')->nullable()->after('categoria_id');
            $table->unsignedInteger('proveedor_id')->nullable()->after('fecha');

            $table->string('folio')->nullable()->after('monto');
            $table->integer('neto')->nullable()->after('folio');
            $table->integer('iva')->nullable()->after('neto');

            $table->renameColumn('monto', 'total');

            $table->foreign('tipo_documento_id')->references('id')->on('tipos_documentos')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('subcategoria_id')->references('id')->on('subcategorias_compras')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onUpdate('cascade')->onDelete('set null');
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
            // Primero eliminar las claves foráneas
            $table->dropForeign(['tipo_documento_id']);
            $table->dropForeign(['subcategoria_id']);
            $table->dropForeign(['proveedor_id']);

            // Luego eliminar las columnas agregadas
            $table->dropColumn('tipo_documento_id');
            $table->dropColumn('subcategoria_id');
            $table->dropColumn('proveedor_id');
            $table->dropColumn('folio');
            $table->dropColumn('neto');
            $table->dropColumn('iva');

            // Finalmente revertir el renombramiento de columna
            $table->renameColumn('total', 'monto');
        });
    }
}
