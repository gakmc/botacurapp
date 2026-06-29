<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSiiFieldsToEgresosTable extends Migration
{
    /**
     * Prepara la tabla egresos para recibir documentos importados desde SII.
     *
     * Cambios:
     *   1. neto          → monto neto del documento (devuelto por SII como montoNeto)
     *   2. iva           → IVA del documento (SII: montoIva). 19% para facturas afectas.
     *   3. fuente        → agrega 'sii' al enum existente (manual|home_assistant|woocommerce|scan)
     *
     * Flujo SII previsto:
     *   API SII (RCV) → importar DTE → crear Egreso con fuente='sii', neto+iva+total del DTE
     *   → vincula proveedor por rut → pago posterior en pagos_egresos
     */
    public function up()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->integer('neto')->nullable()->after('total')
                  ->comment('Monto neto del documento (SII: montoNeto)');
            $table->integer('iva')->nullable()->after('neto')
                  ->comment('IVA del documento (SII: montoIva)');
        });

        // Extender el enum fuente añadiendo 'sii'
        // MySQL no permite ALTER COLUMN en enums con Blueprint directamente,
        // se debe redefinir el tipo completo via SQL.
        DB::statement("
            ALTER TABLE egresos
            MODIFY COLUMN fuente
                ENUM('manual','home_assistant','woocommerce','scan','sii')
                NOT NULL DEFAULT 'manual'
        ");
    }

    public function down()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->dropColumn(['neto', 'iva']);
        });

        // Revertir enum a su estado anterior (sin 'sii')
        DB::statement("
            ALTER TABLE egresos
            MODIFY COLUMN fuente
                ENUM('manual','home_assistant','woocommerce','scan')
                NOT NULL DEFAULT 'manual'
        ");
    }
}
