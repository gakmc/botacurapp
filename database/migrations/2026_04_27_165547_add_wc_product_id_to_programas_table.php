<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWcProductIdToProgramasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programas', function (Blueprint $table) {
            // ID del producto en WooCommerce que representa este programa
            $table->unsignedInteger('wc_product_id')
                  ->nullable()
                  ->unique()
                  ->after('id')
                  ->comment('ID del producto en WooCommerce asociado a este programa');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->dropColumn('wc_product_id');
        });
    }
}
