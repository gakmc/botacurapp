<?php

namespace App\Http\Controllers;

use App\Services\BotInteresFechasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Consultas por correo de dias no habilitados (las manda n8n desde el borrador
 * "NO ENVIAR - INTERES FECHAS" que deja la tarea de correos). 15/10/2026.
 * Body: {"consultas":[{"mensaje_id","correo","nombre","telefono","fecha":"AAAA-MM-DD","personas","programa_id"}]}
 */
class InteresFechasCorreoController extends Controller
{
    public function store(Request $request)
    {
        $lista = $request->input('consultas', []);
        if (!is_array($lista)) {
            return response()->json(['ok' => false, 'error' => 'consultas debe ser una lista'], 422);
        }
        $res = [];
        foreach (array_slice($lista, 0, 100) as $c) {
            $res[] = $this->una(is_array($c) ? $c : []);
        }
        return response()->json(['ok' => true, 'resultado' => $res]);
    }

    private function una(array $c): array
    {
        $correo = mb_strtolower(trim((string) ($c['correo'] ?? '')));
        try {
            $fecha = Carbon::parse((string) ($c['fecha'] ?? ''))->toDateString();
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'fecha_invalida', 'correo' => $correo];
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'correo_invalido', 'fecha' => $fecha];
        }
        if ($fecha <= now()->toDateString()) {
            return ['ok' => false, 'error' => 'fecha_pasada', 'fecha' => $fecha];
        }
        if (\App\FechaDisponible::where('fecha', $fecha)->where('habilitada', true)->exists()) {
            return ['ok' => false, 'error' => 'ya_habilitada', 'fecha' => $fecha];
        }
        $msg = trim((string) ($c['mensaje_id'] ?? ''));
        $claveMsg = $msg !== '' ? 'interes_correo_' . md5($msg . '|' . $fecha) : null;
        if ($claveMsg && Cache::has($claveMsg)) {
            return ['ok' => true, 'fecha' => $fecha, 'repetido' => true];
        }
        // Si el cliente dejo su telefono, se junta con la fila de WhatsApp; si no, clave propia del correo.
        $tel = preg_replace('/\D/', '', (string) ($c['telefono'] ?? ''));
        if (strlen($tel) === 9) {
            $tel = '56' . $tel;
        }
        if (strlen($tel) < 11 || strlen($tel) > 15) {
            $tel = 'm' . substr(sha1($correo), 0, 19);
        }
        $nombre = mb_substr(trim((string) ($c['nombre'] ?? '')), 0, 110) ?: null;
        $extra = [];
        if (!empty($c['personas']) && (int) $c['personas'] > 0) {
            $extra['personas'] = min(500, (int) $c['personas']);
        }
        if (!empty($c['programa_id']) && (int) $c['programa_id'] > 0) {
            $extra['programa_id'] = (int) $c['programa_id'];
        }
        $fila = DB::table('bot_interes_fechas')->where('telefono', $tel)->where('fecha', $fecha)->first();
        if ($fila) {
            DB::table('bot_interes_fechas')->where('id', $fila->id)->update($extra + [
                'consultas'  => DB::raw('consultas + 1'),
                'correo'     => $fila->correo ?: $correo,
                'nombre'     => $fila->nombre ?: ($nombre ? '✉ ' . $nombre : null),
                'estado'     => in_array($fila->estado, ['avisado', 'descartado'], true) ? $fila->estado : 'interesado',
                'anotado_at' => $fila->anotado_at ?: now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('bot_interes_fechas')->insert($extra + [
                'telefono'   => $tel,
                'nombre'     => '✉ ' . ($nombre ?: $correo),
                'correo'     => $correo,
                'origen'     => 'correo',
                'fecha'      => $fecha,
                'consultas'  => 1,
                'estado'     => 'interesado',
                'anotado_at' => now(),
                'nota'       => 'Consulto por correo: ' . $correo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if ($claveMsg) {
            Cache::put($claveMsg, 1, now()->addDays(120));
        }
        BotInteresFechasService::revisarUmbral($fecha);
        return ['ok' => true, 'fecha' => $fecha];
    }
}
