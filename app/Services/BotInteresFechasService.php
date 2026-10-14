<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Días pedidos sin habilitar (14/10/2026).
 *
 * Cuando el bot verifica una fecha y el sistema responde "fecha_no_habilitada" (lunes a miércoles
 * no festivos o un día cerrado de un mes ya abierto), se registra la consulta. Si el cliente acepta
 * que lo anotemos, queda como "interesado" y se le puede avisar cuando el día se habilite.
 *
 * Estados: consulta (preguntó, no pidió aviso) · interesado (pidió aviso) · avisado · descartado.
 * Tabla: bot_interes_fechas (una fila por teléfono y fecha).
 */
class BotInteresFechasService
{
    const TABLA = 'bot_interes_fechas';

    /** Personas totales (consultas + anotados) desde las que se sugiere evaluar abrir el día. */
    public static function umbral(): int
    {
        return (int) env('BOT_INTERES_UMBRAL', 10);
    }

    public static function telefono(string $t): string
    {
        $d = preg_replace('/\D/', '', $t);
        if (strlen($d) === 9) {
            $d = '56' . $d;
        }
        return $d;
    }

    private static function fecha($f): ?string
    {
        try {
            return Carbon::parse((string) $f)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function nombreDesdeConversacion(string $tel): ?string
    {
        try {
            if (!Schema::hasTable('bot_conversaciones')) {
                return null;
            }
            $corto = substr($tel, -9);
            foreach (['nombre', 'nombre_cliente', 'cliente_nombre'] as $col) {
                if (Schema::hasColumn('bot_conversaciones', $col)) {
                    $n = DB::table('bot_conversaciones')->whereIn('usuario_id', [$tel, $corto])
                        ->orderByDesc('id')->value($col);
                    return $n ?: null;
                }
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    private static function datosOpcionales(array $datos): array
    {
        $out = [];
        if (!empty($datos['personas']) && (int) $datos['personas'] > 0) {
            $out['personas'] = min(500, (int) $datos['personas']);
        }
        if (!empty($datos['programa_id']) && (int) $datos['programa_id'] > 0) {
            $out['programa_id'] = (int) $datos['programa_id'];
        }
        return $out;
    }

    /**
     * Fechas que el cliente escribió en su mensaje ("lunes 19 de octubre", "19/10", "2026-10-19").
     * Sin año: el año en curso, o el siguiente si ya pasó. Solo días (no "los lunes" sin número).
     */
    public static function fechasEnTexto(string $texto): array
    {
        $t = mb_strtolower($texto);
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
        $meses = ['enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6, 'julio' => 7,
            'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12];
        $hoy = now()->startOfDay();
        $out = [];
        $armar = function ($d, $m, $y) use ($hoy, &$out) {
            $sinAnio = !$y;
            $d = (int) $d;
            $m = (int) $m;
            if ($d < 1 || $d > 31 || $m < 1 || $m > 12) {
                return;
            }
            $y = $y ? (int) $y : (int) $hoy->year;
            if ($y < 100) {
                $y += 2000;
            }
            if (!checkdate($m, $d, $y)) {
                return;
            }
            $f = Carbon::create($y, $m, $d)->startOfDay();
            if ($f->lt($hoy) && $sinAnio) {
                $f->addYear();
            }
            $out[] = $f->toDateString();
        };
        if (preg_match_all('/\b(\d{1,2})\s*(?:de\s+)?(' . implode('|', array_keys($meses)) . ')(?:\s+(?:de(?:l)?\s+)?(\d{4}))?/u', $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $armar($x[1], $meses[$x[2]], $x[3] ?? null);
            }
        }
        if (preg_match_all('/\b(\d{4})-(\d{2})-(\d{2})\b/', $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $armar($x[3], $x[2], $x[1]);
            }
            $t = preg_replace('/\b\d{4}-\d{2}-\d{2}\b/', ' ', $t);
        }
        if (preg_match_all('/\b(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{2,4}))?\b/', $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $armar($x[1], $x[2], $x[3] ?? null);
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Día cerrado de un mes ya abierto: futuro, dentro de 120 días, no habilitado en el calendario
     * y con al menos un día habilitado en ese mismo mes (meses sin abrir no cuentan).
     */
    public static function esDiaCerradoDeMesAbierto(string $fecha): bool
    {
        $f = Carbon::parse($fecha)->startOfDay();
        if ($f->lte(now()->startOfDay()) || $f->gt(now()->addDays(120))) {
            return false;
        }
        if (\App\FechaDisponible::where('fecha', $f->toDateString())->where('habilitada', true)->exists()) {
            return false;
        }
        return \App\FechaDisponible::where('habilitada', true)
            ->whereBetween('fecha', [$f->copy()->startOfMonth()->toDateString(), $f->copy()->endOfMonth()->toDateString()])
            ->exists();
    }

    /**
     * Revisa el mensaje del cliente antes de que responda la IA. Si pide un día cerrado de un mes abierto,
     * registra la consulta y devuelve la nota [Sistema-interes] para agregar a su mensaje ('' si no aplica).
     */
    public static function notaParaMensaje(string $usuarioId, string $textoCliente, $conv = null, bool $simulacion = false): string
    {
        try {
            if (!Schema::hasTable(self::TABLA)) {
                return '';
            }
            $cerradas = array_values(array_filter(self::fechasEnTexto($textoCliente), function ($f) {
                return self::esDiaCerradoDeMesAbierto($f);
            }));
            if (!$cerradas) {
                return '';
            }
            $datos = [
                'personas'    => $conv->cantidad_personas ?? null,
                'programa_id' => $conv->id_programa ?? null,
            ];
            $nombre = $conv->nombre_cliente ?? null;
            $notas = [];
            foreach ($cerradas as $f) {
                if (!$simulacion) {
                    self::registrarConsulta($usuarioId, $f, $datos, $nombre);
                }
                $dia = Carbon::parse($f)->locale('es')->isoFormat('dddd D [de] MMMM');
                $notas[] = $dia . ' (' . $f . ')';
            }
            return "\n\n[Sistema-interes: el cliente pidió " . implode(' y ', $notas) . ', que NO está habilitado (ese día no abrimos). '
                . 'Dile que ese día no abrimos, ofrécele otra fecha (verifícala con verificar_disponibilidad) y, en el mismo mensaje, '
                . 'pregúntale si quiere que lo anotemos para avisarle por este chat si habilitamos ese día. '
                . 'Solo si responde que sí, usa la acción "anotar_interes" con datos {"fecha":"' . $cerradas[0] . '","personas":<número o null>,"programa_id":<id o null>}. '
                . 'No prometas que se abrirá ni lo anotes sin su permiso.]';
        } catch (\Throwable $e) {
            Log::warning('[BotInteres] notaParaMensaje: ' . $e->getMessage());
            return '';
        }
    }

    /** El bot verificó la fecha y no está habilitada: suma la consulta (sin pedir permiso todavía). */
    public static function registrarConsulta(string $usuarioId, string $fecha, array $datos, ?string $nombre = null): void
    {
        try {
            $tel = self::telefono($usuarioId);
            $fecha = self::fecha($fecha);
            if (!$tel || !$fecha || $fecha <= now()->toDateString()) {
                return;
            }
            $fila = DB::table(self::TABLA)->where('telefono', $tel)->where('fecha', $fecha)->first();
            $extra = self::datosOpcionales($datos);
            if ($fila) {
                $reciente = $fila->updated_at && Carbon::parse($fila->updated_at)->gt(now()->subMinutes(30));
                DB::table(self::TABLA)->where('id', $fila->id)->update($extra + [
                    'consultas'  => $reciente ? $fila->consultas : DB::raw('consultas + 1'),
                    'updated_at' => now(),
                ]);
                return;
            }
            DB::table(self::TABLA)->insert($extra + [
                'telefono'   => $tel,
                'nombre'     => $nombre ?: self::nombreDesdeConversacion($tel),
                'fecha'      => $fecha,
                'consultas'  => 1,
                'estado'     => 'consulta',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[BotInteres] No se pudo registrar la consulta: ' . $e->getMessage());
        }
    }

    /** Acción "anotar_interes": el cliente aceptó que le avisemos si se habilita el día. */
    public static function anotar(string $usuarioId, array $datos, ?string $nombre = null): array
    {
        $tel = self::telefono($usuarioId);
        $fecha = self::fecha($datos['fecha'] ?? '');
        if (!$tel || !$fecha) {
            return ['ok' => false, 'error' => 'fecha_invalida', 'mensaje' => 'La fecha va en formato AAAA-MM-DD.'];
        }
        if ($fecha <= now()->toDateString()) {
            return ['ok' => false, 'error' => 'fecha_pasada', 'mensaje' => 'Esa fecha ya pasó.'];
        }
        if (\App\FechaDisponible::where('fecha', $fecha)->where('habilitada', true)->exists()) {
            return ['ok' => false, 'error' => 'ya_habilitada', 'mensaje' => 'Ese día ya está habilitado: verifica la disponibilidad y ofrécele reservar.'];
        }
        $extra = self::datosOpcionales($datos);
        $fila = DB::table(self::TABLA)->where('telefono', $tel)->where('fecha', $fecha)->first();
        if ($fila) {
            $estado = in_array($fila->estado, ['avisado'], true) ? $fila->estado : 'interesado';
            DB::table(self::TABLA)->where('id', $fila->id)->update($extra + [
                'estado'     => $estado,
                'anotado_at' => $fila->anotado_at ?: now(),
                'nombre'     => $fila->nombre ?: ($nombre ?: self::nombreDesdeConversacion($tel)),
                'updated_at' => now(),
            ]);
        } else {
            DB::table(self::TABLA)->insert($extra + [
                'telefono'   => $tel,
                'nombre'     => $nombre ?: self::nombreDesdeConversacion($tel),
                'fecha'      => $fecha,
                'consultas'  => 1,
                'estado'     => 'interesado',
                'anotado_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        try {
            BotCasoService::registrar('interes_dia_cerrado', $tel, ['fecha' => $fecha] + $extra);
        } catch (\Throwable $e) {
        }
        return ['ok' => true, 'fecha' => $fecha, 'mensaje' => 'Quedó anotado para avisarle si se habilita ese día.'];
    }

    private static function nombresProgramas(): array
    {
        try {
            if (!Schema::hasTable('programas')) {
                return [];
            }
            foreach (['nombre_programa', 'nombre'] as $col) {
                if (Schema::hasColumn('programas', $col)) {
                    return DB::table('programas')->pluck($col, 'id')->toArray();
                }
            }
        } catch (\Throwable $e) {
        }
        return [];
    }

    /** Resumen por fecha futura (sin descartados), con el detalle de cada persona. */
    public static function resumen(): array
    {
        if (!Schema::hasTable(self::TABLA)) {
            return [];
        }
        $filas = DB::table(self::TABLA)
            ->where('fecha', '>', now()->toDateString())
            ->where('estado', '<>', 'descartado')
            ->orderBy('fecha')->orderBy('created_at')
            ->get();
        if ($filas->isEmpty()) {
            return [];
        }
        $programas = self::nombresProgramas();
        $habilitadas = \App\FechaDisponible::whereIn('fecha', $filas->pluck('fecha')->unique()->values()->all())
            ->where('habilitada', true)->get()
            ->map(function ($f) {
                return Carbon::parse($f->fecha)->toDateString();
            })->all();
        $umbral = self::umbral();
        $out = [];
        foreach ($filas->groupBy('fecha') as $fecha => $grupo) {
            $per = function ($g) {
                return $g->sum(function ($r) {
                    return max(1, (int) $r->personas);
                });
            };
            $anotados = $grupo->whereIn('estado', ['interesado', 'avisado']);
            $porAvisar = $grupo->where('estado', 'interesado');
            $personas = $per($grupo);
            $out[] = (object) [
                'fecha'              => $fecha,
                'etiqueta'           => Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM'),
                'consultas'          => $grupo->count(),
                'personas'           => $personas,
                'anotados'           => $anotados->count(),
                'personas_anotadas'  => $per($anotados),
                'por_avisar'         => $porAvisar->count(),
                'avisados'           => $grupo->where('estado', 'avisado')->count(),
                'habilitada'         => in_array($fecha, $habilitadas, true),
                'evaluar'            => $personas > $umbral,
                'personas_detalle'   => $grupo->map(function ($r) use ($programas) {
                    $r->programa = $r->programa_id ? ($programas[$r->programa_id] ?? ('Programa #' . $r->programa_id)) : null;
                    return $r;
                })->values(),
            ];
        }
        return $out;
    }

    public static function textoAviso(?string $nombre, string $fecha): string
    {
        $dia = Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $hola = $nombre ? 'Hola ' . trim(explode(' ', $nombre)[0]) : 'Hola';
        return $hola . ' 👋 Te escribimos de Botacura: habilitamos el ' . $dia
            . ', el día que nos consultaste. Si todavía quieren venir, respóndenos por aquí y te ayudamos a reservar. '
            . 'El cupo se asegura al hacer la reserva con el abono.';
    }

    /** ¿El cliente escribió en las últimas 23 h? (ventana de 24 h de WhatsApp para texto libre). */
    private static function dentroDeVentana(string $tel): bool
    {
        try {
            if (Schema::hasTable('bot_conversaciones') && Schema::hasColumn('bot_conversaciones', 'updated_at')) {
                $u = DB::table('bot_conversaciones')->whereIn('usuario_id', [$tel, substr($tel, -9)])->max('updated_at');
                return $u && Carbon::parse($u)->gt(now()->subHours(23));
            }
        } catch (\Throwable $e) {
        }
        return false;
    }

    /** Envía por la API de WhatsApp (plantilla si está configurada; si no, texto dentro de la ventana de 24 h). */
    private static function enviar(string $tel, ?string $nombre, string $fecha): array
    {
        $token = env('META_WHATSAPP_TOKEN') ?: config('services.whatsapp.token');
        $pid = env('META_PHONE_NUMBER_ID') ?: config('services.whatsapp.phone_number_id');
        if (!$token || !$pid) {
            return ['ok' => false, 'motivo' => 'Falta META_WHATSAPP_TOKEN o META_PHONE_NUMBER_ID'];
        }
        $plantilla = trim((string) env('BOT_PLANTILLA_DIA_HABILITADO', ''));
        if ($plantilla !== '') {
            $dia = Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
            $nom = $nombre ? trim(explode(' ', $nombre)[0]) : 'hola';
            $payload = [
                'messaging_product' => 'whatsapp', 'to' => $tel, 'type' => 'template',
                'template' => [
                    'name' => $plantilla,
                    'language' => ['code' => env('BOT_PLANTILLA_IDIOMA', 'es')],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => $nom], ['type' => 'text', 'text' => $dia]],
                    ]],
                ],
            ];
            $via = 'plantilla';
        } elseif (self::dentroDeVentana($tel)) {
            $payload = ['messaging_product' => 'whatsapp', 'to' => $tel, 'type' => 'text',
                'text' => ['body' => self::textoAviso($nombre, $fecha)]];
            $via = 'api';
        } else {
            return ['ok' => false, 'motivo' => 'Escribió hace más de 24 h: necesita plantilla o aviso manual'];
        }
        try {
            $v = env('META_GRAPH_VERSION', 'v21.0');
            $ch = curl_init("https://graph.facebook.com/{$v}/{$pid}/messages");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
            $raw = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errCurl = curl_error($ch);
            curl_close($ch);
            $body = json_decode((string) $raw, true) ?: [];
            if ($code > 0 && $code < 300 && !empty($body['messages'][0]['id'])) {
                return ['ok' => true, 'via' => $via];
            }
            return ['ok' => false, 'motivo' => mb_substr($body['error']['message'] ?? ($errCurl ?: 'HTTP ' . $code), 0, 180)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'motivo' => mb_substr($e->getMessage(), 0, 180)];
        }
    }

    /** Botón "Avisar": solo si el día ya está habilitado; avisa a los que pidieron aviso y aún no lo reciben. */
    public static function avisarFecha(string $fecha, ?int $userId): array
    {
        $fecha = self::fecha($fecha);
        if (!$fecha) {
            return ['ok' => false, 'mensaje' => 'Fecha inválida.'];
        }
        if (!\App\FechaDisponible::where('fecha', $fecha)->where('habilitada', true)->exists()) {
            return ['ok' => false, 'mensaje' => 'Primero habilita el día en el calendario; después avisa.'];
        }
        $filas = DB::table(self::TABLA)->where('fecha', $fecha)->where('estado', 'interesado')->get();
        $enviados = 0;
        $fallas = [];
        foreach ($filas as $r) {
            $res = self::enviar($r->telefono, $r->nombre, $fecha);
            if ($res['ok']) {
                $enviados++;
                DB::table(self::TABLA)->where('id', $r->id)->update([
                    'estado' => 'avisado', 'avisado_at' => now(), 'avisado_por' => $userId,
                    'aviso_via' => $res['via'], 'nota' => null, 'updated_at' => now(),
                ]);
                try {
                    BotCasoService::registrar('interes_avisado', $r->telefono, ['fecha' => $fecha]);
                } catch (\Throwable $e) {
                }
            } else {
                $fallas[] = ($r->nombre ?: '+' . $r->telefono) . ': ' . $res['motivo'];
                DB::table(self::TABLA)->where('id', $r->id)->update(['nota' => mb_substr($res['motivo'], 0, 250), 'updated_at' => now()]);
            }
        }
        $msg = "Avisados: {$enviados} de " . count($filas) . '.';
        if ($fallas) {
            $msg .= ' Sin enviar (usa el botón WhatsApp de cada persona): ' . implode(' · ', $fallas);
        }
        return ['ok' => true, 'mensaje' => $msg];
    }

    public static function cambiarEstado(int $id, string $estado, ?int $userId): bool
    {
        if (!in_array($estado, ['interesado', 'avisado', 'descartado'], true)) {
            return false;
        }
        $upd = ['estado' => $estado, 'updated_at' => now()];
        if ($estado === 'avisado') {
            $upd += ['avisado_at' => now(), 'avisado_por' => $userId, 'aviso_via' => 'manual', 'nota' => null];
        }
        return DB::table(self::TABLA)->where('id', $id)->update($upd) > 0;
    }
}
