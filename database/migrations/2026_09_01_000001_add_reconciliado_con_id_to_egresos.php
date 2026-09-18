<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReconciliadoConIdToEgresos extends Migration
{
    public function up()
    {
        Schema::table('egresos', function (Blueprint $table) {
            // Cuando un egreso registrado por una fuente estimada (ej. gas_iot)
            // queda cubierto por la factura real del mismo proveedor/periodo
            // (ej. fuente sii), se apunta aqui al id de esa factura real.
            // Los egresos reconciliados se excluyen de los totales de reportes
            // para no contar el gasto dos veces (el estimado + la factura real).
            $table->unsignedBigInteger('reconciliado_con_id')->nullable()->after('estado');
            $table->index('reconciliado_con_id');
        });
    }

    public function down()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->dropIndex(['reconciliado_con_id']);
            $table->dropColumn('reconciliado_con_id');
        });
    }
}
