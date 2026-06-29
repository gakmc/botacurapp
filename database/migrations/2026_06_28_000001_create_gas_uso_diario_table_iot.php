<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla gas_uso_diario (BD IoT).
 *
 * Registra las horas de encendido real del calefont por día y por sector.
 * Para tinajas: horas detectadas por variación de temperatura (sensor sonoff).
 * Para casa/cocina: días en uso (siempre 1 por registro).
 *
 * Permite calcular el consumo acumulado hasta cada cambio de cilindro,
 * con granularidad diaria para análisis histórico.
 */
class CreateGasUsoDiarioTableIot extends Migration
{
    public function up()
    {
        Schema::connection('mysql_iot')->create('gas_uso_diario', function (Blueprint $table) {
            $table->increments('id');

            $table->enum('lugar', ['tinaja_1', 'tinaja_2', 'gas_casa', 'gas_cocina']);
            $table->date('fecha');

            // Horas de encendido calefont ese día (tinajas) o días en uso (casa/cocina)
            $table->decimal('horas_uso', 8, 4)->default(0);
            $table->string('unidad', 20)->default('horas')->comment('horas | dias');

            // Referencia al cilindro activo ese día (sin FK — diferente BD)
            $table->unsignedInteger('gas_instalacion_id')->nullable()
                  ->comment('ID del cilindro activo ese día en gas_instalaciones');

            $table->enum('origen', ['home_assistant', 'manual'])->default('home_assistant');
            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->unique(['lugar', 'fecha']);
            $table->index(['lugar', 'fecha']);
        });
    }

    public function down()
    {
        Schema::connection('mysql_iot')->dropIfExists('gas_uso_diario');
    }
}
