<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Gift Cards: fecha de compra; vencen 45 dias despues (15/10/2026). */
class AddFechaCompraToGiftCards extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('gift_cards', 'fecha_compra')) {
            Schema::table('gift_cards', function (Blueprint $table) {
                $table->date('fecha_compra')->nullable()->after('monto');
            });
        }
        DB::statement('UPDATE gift_cards SET fecha_compra = DATE(created_at) WHERE fecha_compra IS NULL AND created_at IS NOT NULL');
    }

    public function down()
    {
        if (Schema::hasColumn('gift_cards', 'fecha_compra')) {
            Schema::table('gift_cards', function (Blueprint $table) {
                $table->dropColumn('fecha_compra');
            });
        }
    }
}
