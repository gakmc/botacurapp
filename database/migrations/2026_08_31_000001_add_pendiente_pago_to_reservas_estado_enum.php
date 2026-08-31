<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El bot de WhatsApp (BotController/BotReservaController) inserta reservas
 * con estado 'pendiente_pago' (distinto de 'pendiente', usado para
 * distinguir reservas creadas por el bot en espera de confirmación de pago
 * vía comprobante de transferencia o Webpay). Ese valor nunca se agregó al
 * ENUM de la columna, causando "Data truncated for column 'estado'" y
 * bloqueando el 100% de las reservas creadas por el bot.
 *
 * Compatible Laravel 6 / PHP 7.2 (ENUM requiere SQL crudo, Doctrine DBAL
 * no soporta ALTER de ENUM directamente).
 */
class AddPendientePagoToReservasEstadoEnum extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE reservas MODIFY estado ENUM('pendiente','pendiente_pago','confirmada','completada','cancelada','no_show') NOT NULL DEFAULT 'pendiente'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE reservas MODIFY estado ENUM('pendiente','confirmada','completada','cancelada','no_show') NOT NULL DEFAULT 'pendiente'");
    }
}
