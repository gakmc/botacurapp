<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddSubcategoriaIdToProveedores extends Migration
{
    public function up()
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->unsignedBigInteger('subcategoria_id')->nullable()->after('correo');
            $table->index('subcategoria_id');
        });
    }
    public function down()
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropIndex(['subcategoria_id']);
            $table->dropColumn('subcategoria_id');
        });
    }
}
