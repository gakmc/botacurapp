<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfirmadoToReservaDesayunoOnce extends Migration
{
    public function up()
    {
        Schema::table('reserva_desayuno_once', function (Blueprint $table) {
            if (!Schema::hasColumn('reserva_desayuno_once', 'confirmado_at')) {
                $table->timestamp('confirmado_at')->nullable();
            }
            if (!Schema::hasColumn('reserva_desayuno_once', 'confirmado_por')) {
                $table->unsignedBigInteger('confirmado_por')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('reserva_desayuno_once', function (Blueprint $table) {
            $table->dropColumn(['confirmado_at', 'confirmado_por']);
        });
    }
}
