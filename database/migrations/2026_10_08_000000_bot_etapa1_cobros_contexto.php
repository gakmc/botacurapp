<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BotEtapa1CobrosContexto extends Migration
{
    private function igual($a, $b)
    {
        return preg_replace('/\s+/u', ' ', trim((string) $a)) === preg_replace('/\s+/u', ' ', trim((string) $b));
    }

    public function up()
    {
        if (!Schema::hasColumn('bot_conversaciones', 'humano_hasta')) {
            Schema::table('bot_conversaciones', function (Blueprint $t) {
                $t->timestamp('humano_hasta')->nullable();
            });
        }

        // Parámetros editables (pestaña "Parámetros")
        $i = 0;
        foreach (\App\Services\BotTarifasService::parametrosDefault() as $clave => $def) {
            $i++;
            if (!DB::table('bot_secciones_texto')->where('clave', $clave)->exists()) {
                DB::table('bot_secciones_texto')->insert([
                    'categoria' => 'parametros', 'clave' => $clave, 'etiqueta' => $def[0], 'contenido' => $def[1],
                    'orden' => $i, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // Tono y estilo (pestaña "Reglas comerciales")
        $reglas = \App\Services\BotPromptService::reglasDefault();
        if (!DB::table('bot_secciones_texto')->where('clave', 'regla_tono')->exists()) {
            DB::table('bot_secciones_texto')->insert([
                'categoria' => 'reglas', 'clave' => 'regla_tono', 'etiqueta' => 'Tono y estilo',
                'contenido' => $reglas['regla_tono'], 'orden' => 8, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Programas S: texto nuevo solo si nadie lo editó
        $viejoS = 'PROGRAMAS DE SEMANA (los que terminan en "S"): son el mismo programa con 10% de descuento pagando por transferencia, para fechas de lunes a viernes habilitadas y que no sean feriado. Por ahora SOLO aplican de marzo a octubre. Para fechas de noviembre a febrero el descuento de semana aún no está definido: no lo ofrezcas ni uses programas S; si preguntan, di que lo estamos definiendo y usa escalar_humano.';
        $filaS = DB::table('bot_secciones_texto')->where('clave', 'regla_programas_semana')->first();
        if ($filaS && $this->igual($filaS->contenido, $viejoS)) {
            DB::table('bot_secciones_texto')->where('id', $filaS->id)->update(['contenido' => $reglas['regla_programas_semana'], 'updated_at' => now()]);
        }

        // Guion: pasos 10 y 11, solo si nadie los editó
        $cambios = [
            '10' => ['"¿Cómo prefieren realizar el pago del abono? Puedes pagar con débito, crédito o transferencia bancaria 💳"
→ Guarda en datos.tipo_pago ("Débito", "Crédito" o "Transferencia")
NOTA: Este paso es OBLIGATORIO antes de crear la reserva. No saltarlo.', 'Pregunta cómo prefieren pagar: transferencia bancaria o tarjeta (débito o crédito, por Webpay).
No des porcentajes ni montos de memoria: los entrega la acción "cotizar". Por transferencia se abona una parte al reservar y el resto el día de la visita; con tarjeta se paga al reservar. Los programas de semana (S) y los que se pagan 100% son solo por transferencia.
→ Guarda en datos.tipo_pago ("Débito", "Crédito" o "Transferencia")
NOTA: Este paso es OBLIGATORIO antes de crear la reserva. No saltarlo.'],
            '11' => ['Presenta el resumen completo (incluyendo extras y total) y usa accion "crear_reserva" con todos los datos.
El sistema creará la reserva en la BD y te devolverá el ID + instrucciones de pago.', 'Usa la acción "cotizar" con todos los datos. Con el resultado presenta el resumen (montos copiados tal cual) y pide confirmación.
Cuando el cliente confirme, usa accion "crear_reserva" con todos los datos. El sistema creará la reserva (o apartará el cupo si es transferencia) y te devolverá el ID e instrucciones de pago.'],
        ];
        foreach ($cambios as $num => $par) {
            $fila = DB::table('bot_pasos_flujo')->where('numero_paso', $num)->first();
            if ($fila && $this->igual($fila->instrucciones, $par[0])) {
                DB::table('bot_pasos_flujo')->where('id', $fila->id)->update(['instrucciones' => $par[1], 'updated_at' => now()]);
            }
        }

        // Programas de semana (S): no reagendables, según políticas
        DB::table('programas')->whereIn('id', [3, 43, 45, 49])->update(['permite_reprogramacion' => 0]);
    }

    public function down()
    {
        DB::table('bot_secciones_texto')->where('categoria', 'parametros')->delete();
        DB::table('bot_secciones_texto')->where('clave', 'regla_tono')->delete();
        if (Schema::hasColumn('bot_conversaciones', 'humano_hasta')) {
            Schema::table('bot_conversaciones', function (Blueprint $t) {
                $t->dropColumn('humano_hasta');
            });
        }
    }
}
