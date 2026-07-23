<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixTasaRetencionInHonorariosBte extends Migration
{
    public function up()
    {
        Schema::table('honorarios_bte', function (Blueprint $table) {
            // decimal(5,4) solo aguanta hasta 9.9999
            // El SII envía p.ej. 15.25 (porcentaje), necesitamos decimal(5,2)
            $table->decimal('tasa_retencion', 5, 2)->default(15.25)->change();
        });
    }

    public function down()
    {
        Schema::table('honorarios_bte', function (Blueprint $table) {
            $table->decimal('tasa_retencion', 5, 4)->default(0.1525)->change();
        });
    }
}
