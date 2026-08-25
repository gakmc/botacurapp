<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecordatorioEnviadoAtToReservasTable extends Migration
{
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->timestamp('recordatorio_enviado_at')->nullable()->after('estado');
        });
    }

    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('recordatorio_enviado_at');
        });
    }
}
