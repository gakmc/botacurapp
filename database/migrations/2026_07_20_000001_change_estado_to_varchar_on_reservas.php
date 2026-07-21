<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cambia reservas.estado de ENUM a VARCHAR(50).
 *
 * El dump de producción trajo la columna como ENUM con valores limitados,
 * lo que impedía insertar 'pendiente_pago' desde el bot WhatsApp.
 * Esta migración convierte la columna a VARCHAR preservando los datos existentes.
 */
class ChangeEstadoToVarcharOnReservas extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `reservas` MODIFY COLUMN `estado` VARCHAR(50) NULL DEFAULT NULL");
    }

    public function down()
    {
        // Solo restaurar si se conocen los valores ENUM originales.
        // Dejamos como VARCHAR por seguridad en el rollback.
        DB::statement("ALTER TABLE `reservas` MODIFY COLUMN `estado` VARCHAR(50) NULL DEFAULT NULL");
    }
}
