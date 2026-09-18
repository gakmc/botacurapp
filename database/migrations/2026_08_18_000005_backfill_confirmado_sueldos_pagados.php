<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix de regresión: la migración anterior agregó 'confirmado' con default
 * false a TODOS los registros existentes en sueldos_pagados. Pero antes de
 * esa migración, la sola existencia de un registro en sueldos_pagados
 * significaba "ya pagado" (no existía el concepto de bono guardado sin
 * confirmar). Eso hizo que las semanas ya pagadas antes de este cambio
 * "perdieran" su estado Pagado en la vista (volvió a aparecer el checkbox
 * "Pagar").
 *
 * Esta migración marca como confirmado=true todos los registros que ya
 * existían (los que quedaron con confirmado=false por el default de la
 * migración anterior), restaurando su estado histórico de "Pagado".
 */
class BackfillConfirmadoSueldosPagados extends Migration
{
    public function up()
    {
        DB::table('sueldos_pagados')
            ->where('confirmado', false)
            ->update([
                'confirmado'    => true,
                'confirmado_at' => DB::raw("COALESCE(confirmado_at, fecha_pago, NOW())"),
            ]);
    }

    public function down()
    {
        // No es seguro revertir automáticamente (no se puede distinguir
        // cuáles ya eran confirmado=true antes de este backfill).
    }
}
