<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWoocommerceOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_orders', function (Blueprint $table) {
            $table->Increments('id');
                        // ── Identificadores WooCommerce ───────────────────────
            $table->unsignedInteger('wc_order_id')->unique();
            $table->string('wc_order_key')->nullable();
            $table->unsignedInteger('wc_product_id')->nullable();

            // ── Datos del cliente (billing estándar) ──────────────
            $table->string('billing_email');
            $table->string('billing_first_name')->nullable();
            $table->string('billing_last_name')->nullable();
            $table->string('billing_phone')->nullable();

            // ── Campos personalizados del checkout ────────────────
            // Confirmado en pedido real: meta_data key "billing_fecha_visita"
            $table->date('fecha_visita_wc')->nullable()
                  ->comment('Campo billing_fecha_visita del checkout WC');
            $table->date('fecha_reservacion_wc')->nullable()
                  ->comment('Campo billing_fecha_reservacion (desactivado en WC)');

            // ── Datos del pedido ──────────────────────────────────
            $table->string('status');
            $table->unsignedInteger('total')->nullable()
                  ->comment('Monto en pesos chilenos (sin decimales)');
            $table->string('currency', 10)->nullable();
            $table->string('payment_method')->nullable()
                  ->comment('Ej: Transbank Webpay Plus');

            // ── Datos de Transbank Webpay ─────────────────────────
            // Confirmados en campos personalizados del pedido real #9358
            $table->string('authorization_code')->nullable()
                  ->comment('authorizationCode de Transbank (ej: 395382) → se usa como folio');
            $table->string('card_number', 10)->nullable()
                  ->comment('Últimos 4 dígitos de la tarjeta (ej: 4223)');
            $table->string('payment_type')->nullable()
                  ->comment('Crédito / Débito / Prepago');
            $table->string('transaction_status')->nullable()
                  ->comment('Autorizada / Rechazada / etc.');
            $table->string('buy_order')->nullable()
                  ->comment('buyOrder de Transbank (ej: wcef25dfbf51101f7202:9358)');
            $table->tinyInteger('installments_number')->default(0)
                  ->comment('Número de cuotas (0 = sin cuotas)');

            // ── Resultado del procesamiento en Laravel ────────────
            $table->enum('procesado', ['pendiente', 'ok', 'error'])->default('pendiente');
            $table->unsignedInteger('reserva_id')->nullable();
            $table->unsignedInteger('cliente_id')->nullable();
            $table->text('error_detalle')->nullable();

            // Payload completo para debugging y auditoría
            $table->json('payload_raw')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_orders');
    }
}
