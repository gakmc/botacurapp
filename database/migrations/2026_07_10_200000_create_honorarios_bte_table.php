<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crear tabla honorarios_bte
 * Almacena boletas de terceros emitidas al RUT de Botacura SpA.
 * Compatible Laravel 6 / PHP 7.2
 */
class CreateHonorariosBteTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('honorarios_bte')) {
            Schema::create('honorarios_bte', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('folio', 30)->unique()->comment('Folio de la BTE');
                $table->string('periodo', 6)->comment('YYYYMM');
                $table->string('estado', 30)->nullable()->comment('Vigente | Anulada');
                $table->string('rut_emisor', 15)->comment('RUT del trabajador');
                $table->string('nombre_emisor', 150)->nullable();
                $table->date('fecha_emision');
                $table->date('fecha_pago')->nullable();
                $table->integer('monto_bruto')->default(0);
                $table->decimal('tasa_retencion', 5, 4)->default(0.1525)->comment('p.ej. 0.1525 = 15.25%');
                $table->integer('monto_retenido')->default(0);
                $table->integer('monto_pagado')->default(0);
                $table->unsignedBigInteger('proveedor_id')->nullable();
                $table->unsignedBigInteger('egreso_id')->nullable();
                $table->timestamp('sincronizado_at')->nullable();
                $table->timestamps();

                $table->index('periodo');
                $table->index('rut_emisor');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('honorarios_bte');
    }
}
