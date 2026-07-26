<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Convierte historial_json a LONGTEXT utf8mb4 para soportar emojis.
 * El error original: SQLSTATE[22007] Incorrect string value '\xF0\x9F...'
 * se produce porque la columna/tabla usa utf8 (3 bytes) en lugar de utf8mb4 (4 bytes).
 */
class FixHistorialJsonCharsetUtf8mb4 extends Migration
{
    public function up()
    {
        // Alterar la columna historial_json a LONGTEXT con utf8mb4
        DB::statement(
            'ALTER TABLE bot_conversaciones
             MODIFY historial_json LONGTEXT
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci
             NULL'
        );
    }

    public function down()
    {
        DB::statement(
            'ALTER TABLE bot_conversaciones
             MODIFY historial_json TEXT
             CHARACTER SET utf8
             COLLATE utf8_unicode_ci
             NULL'
        );
    }
}
