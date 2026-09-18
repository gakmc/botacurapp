<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddWellnessToProgramasEspacioTipo extends Migration
{
    public function up()
    {
        if ($this->enumHasWellness()) {
            return;
        }

        DB::statement("ALTER TABLE programas MODIFY espacio_tipo ENUM('estacion_economico','estacion_intermedio','estacion_full','terraza','reposera','wellness') NULL COMMENT 'Tipo de espacio físico que ocupa el programa'");
    }

    public function down()
    {
        if (!$this->enumHasWellness()) {
            return;
        }

        DB::statement("ALTER TABLE programas MODIFY espacio_tipo ENUM('estacion_economico','estacion_intermedio','estacion_full','terraza','reposera') NULL COMMENT 'Tipo de espacio físico que ocupa el programa'");
    }

    private function enumHasWellness()
    {
        $column = DB::selectOne("SHOW COLUMNS FROM programas WHERE Field = 'espacio_tipo'");

        return $column && str_contains($column->Type, "'wellness'");
    }
}
