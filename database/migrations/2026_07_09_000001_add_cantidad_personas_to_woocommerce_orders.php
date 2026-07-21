<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCantidadPersonasToWoocommerceOrders extends Migration
{
    public function up()
    {
        Schema::table('woocommerce_orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('cantidad_personas')
                  ->nullable()
                  ->after('wc_product_id')
                  ->comment('Suma de quantities en line_items del pedido WC (cuántas personas)');
        });
    }

    public function down()
    {
        Schema::table('woocommerce_orders', function (Blueprint $table) {
            $table->dropColumn('cantidad_personas');
        });
    }
}
