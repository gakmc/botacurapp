<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BotAprendizajeService
 *
 * - Registra eventos del bot (derivaciones, montos bloqueados, reglas rechazadas, reservas).
 * - Calcula el embudo de reservas y dónde se cortan las conversaciones.
 * - Analiza conversaciones con IA y propone aprendizajes (quedan "sugeridos" hasta que
 *   alguien del equipo los apruebe en Configuración del bot → Aprendizajes).
 * - Los aprendizajes aprobados y activos se agregan al prompt del bot.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotAprendizajeService
{
    const TEL_PRUEBAS = '569000001';

    public static function evento(string $tipo, ?string $telefono = null, array $detalle = [])
    {
        try {
            if ($telefono && strpos($telefono, self::TEL_PRUEBAS) === 0) {
                return;
            }
            DB::table('bot_eventos')->insert([
                'tipo'       => mb_substr($tipo, 0, 40),
                'telefono'   => $telefono,
                'detalle'    => $detalle ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[BotAprendizaje] No se pudo registrar evento: ' . $e->getMessage());
        }
    }

    /** Texto de aprendizajes aprobados para el prompt. */
    public static function textoAprobados(): string
    {
        try {
            $filas = DB::table('bot_aprendizajes')->where('estado', 'aprobado')->where('activo', 1)->orderBy('id')->limit(40)->get();
        } catch (\Throwable $e) {
            return '';
        }
        $out = [];
        foreach ($filas as $f) {
            $out[] = '- ' . trim(preg_replace('/\s+/', ' ', $f->propuesta));
        }
        return implode("\n", $out);
    }

    // ───────────────────────── Etapas de cada conversación ─────────────────────────

    const ETAPAS = [
        0 => 'Consulta',
        1 => 'Fecha consultada',
        2 => 'Ubicación',
        3 => 'Cotización',
        4 => 'Reserva o cupo apartado',
        5 => 'Pagada',
    ];

    public static function etapa($conv): array
    {
        $hist = json_decode((string) $conv->historial_json, true) ?: [];
        $h = '';
        foreach ($hist as $m) {
            $h .= "\n" . (is_string($m['content'] ?? null) ? $m['content'] : json_encode($m['content'] ?? ''));
        }
        $e = 0;
        if (strpos($h, '[Sistema-disponibilidad') !== false) {
            $e = 1;
        }
        if (strpos($h, '[Sistema-ubicaciones') !== false) {
            $e = 2;
        }
        if (strpos($h, '[Sistema-cotizacion') !== false) {
            $e = 3;
        }
        if (preg_match('/\[Sistema-reserva: \{"ok":true/u', $h) || strpos($h, '[Sistema-comprobante:') !== false || !empty($conv->id_reserva)) {
            $e = 4;
        }
        if (!empty($conv->id_reserva)) {
            $pagada = DB::table('ventas')->where('id_reserva', $conv->id_reserva)
                ->where(function ($q) {
                    $q->whereNotNull('verificado_at')->orWhereIn('estado_pago', ['pagado', 'aprobado', 'confirmado', 'autorizado']);
                })->exists();
            if ($pagada) {
                $e = 5;
            }
        }
        $derivada = (bool) $conv->requiere_humano || strpos($h, '[Equipo Botacura respondió]') !== false;
        $ultimo = end($hist);
        $abandonada = $e < 4 && !$derivada && $ultimo && ($ultimo['role'] ?? '') === 'assistant'
            && Carbon::parse($conv->updated_at)->lt(now()->subHours(24));
        return ['etapa' => $e, 'derivada' => $derivada, 'abandonada' => $abandonada, 'turnos' => count($hist)];
    }

    private static function conversacionesQuery(int $dias)
    {
        return DB::table('bot_conversaciones')
            ->where('updated_at', '>=', now()->subDays($dias))
            ->where('usuario_id', 'not like', self::TEL_PRUEBAS . '%')
            ->where('historial_json', '<>', '[]');
    }

    public static function metricas(int $dias = 30): array
    {
        $convs = self::conversacionesQuery($dias)->select('id', 'usuario_id', 'historial_json', 'id_reserva', 'requiere_humano', 'updated_at')->get();
        $embudo = array_fill(0, 6, 0);
        $cortes = array_fill(0, 6, 0);
        $derivadas = 0;
        $abandonadas = 0;
        foreach ($convs as $c) {
            $et = self::etapa($c);
            for ($i = 0; $i <= $et['etapa']; $i++) {
                $embudo[$i]++;
            }
            if ($et['derivada']) {
                $derivadas++;
            }
            if ($et['abandonada']) {
                $abandonadas++;
                $cortes[$et['etapa']]++;
            }
        }
        $eventos = collect();
        try {
            $eventos = DB::table('bot_eventos')->where('created_at', '>=', now()->subDays($dias))->get();
        } catch (\Throwable $e) {
        }
        $motivos = [];
        foreach ($eventos->where('tipo', 'derivado') as $ev) {
            $d = json_decode($ev->detalle, true) ?: [];
            $m = mb_substr(trim((string) ($d['motivo'] ?? 'Sin motivo')), 0, 80);
            $motivos[$m] = ($motivos[$m] ?? 0) + 1;
        }
        arsort($motivos);
        $reglas = [];
        foreach ($eventos->where('tipo', 'regla_rechazo') as $ev) {
            $d = json_decode($ev->detalle, true) ?: [];
            foreach ((array) ($d['codigos'] ?? []) as $cod) {
                $reglas[$cod] = ($reglas[$cod] ?? 0) + 1;
            }
        }
        arsort($reglas);
        return [
            'dias'          => $dias,
            'total'         => $convs->count(),
            'embudo'        => $embudo,
            'cortes'        => $cortes,
            'derivadas'     => $derivadas,
            'abandonadas'   => $abandonadas,
            'motivos'       => array_slice($motivos, 0, 6, true),
            'reglas'        => array_slice($reglas, 0, 6, true),
            'montos_bloq'   => $eventos->where('tipo', 'monto_bloqueado')->count(),
            'vacias'        => $eventos->where('tipo', 'respuesta_vacia')->count(),
            'errores_ia'    => $eventos->where('tipo', 'error_ia')->count(),
            'sugeridos'     => DB::table('bot_aprendizajes')->where('estado', 'sugerido')->count(),
            'ultimo_analisis' => DB::table('bot_aprendizajes')->where('origen', 'ia')->max('created_at'),
        ];
    }

    public static function conversacionesRecientes(int $dias = 14, int $limite = 30)
    {
        $convs = self::conversacionesQuery($dias)
            ->orderBy('updated_at', 'desc')->limit($limite)
            ->select('id', 'usuario_id', 'nombre_cliente', 'historial_json', 'id_reserva', 'requiere_humano', 'updated_at', 'ultimo_mensaje')->get();
        return $convs->map(function ($c) {
            $et = self::etapa($c);
            return (object) [
                'id'         => $c->id,
                'telefono'   => $c->usuario_id,
                'nombre'     => $c->nombre_cliente ?: 'Cliente',
                'etapa'      => self::ETAPAS[$et['etapa']],
                'etapa_num'  => $et['etapa'],
                'derivada'   => $et['derivada'],
                'abandonada' => $et['abandonada'],
                'turnos'     => $et['turnos'],
                'ultimo'     => mb_substr((string) $c->ultimo_mensaje, 0, 90),
                'fecha'      => Carbon::parse($c->updated_at),
            ];
        });
    }

    /** Transcripción legible: separa lo que escribió el cliente, el bot, el equipo y el sistema. */
    public static function transcripcion(int $id): array
    {
        $c = DB::table('bot_conversaciones')->where('id', $id)->first();
        if (!$c) {
            return [];
        }
        $out = [];
        foreach (json_decode((string) $c->historial_json, true) ?: [] as $m) {
            $txt = is_string($m['content'] ?? null) ? $m['content'] : json_encode($m['content'] ?? '', JSON_UNESCAPED_UNICODE);
            if (($m['role'] ?? '') === 'user') {
                $partes = preg_split('/\n\n(?=\[Sistema)/u', $txt);
                $cliente = trim(array_shift($partes));
                if ($cliente !== '' && strpos($cliente, '[Sistema') !== 0) {
                    $out[] = ['de' => 'cliente', 'texto' => $cliente];
                } elseif ($cliente !== '') {
                    array_unshift($partes, $cliente);
                }
                foreach ($partes as $p) {
                    $out[] = ['de' => 'sistema', 'texto' => mb_substr(trim($p), 0, 600)];
                }
            } else {
                foreach (preg_split('/\n\n(?=\[Equipo Botacura respondió\])/u', $txt) as $p) {
                    if (strpos($p, '[Equipo Botacura respondió]') === 0) {
                        $out[] = ['de' => 'equipo', 'texto' => trim(substr($p, strlen('[Equipo Botacura respondió]:')))];
                    } elseif (trim($p) !== '') {
                        $out[] = ['de' => 'bot', 'texto' => trim($p)];
                    }
                }
            }
        }
        return $out;
    }

    // ───────────────────────── Análisis con IA ─────────────────────────

    public static function analizar(int $dias = 7): array
    {
        @set_time_limit(240);
        $convs = self::conversacionesQuery($dias)->orderBy('updated_at', 'desc')->limit(40)
            ->select('id', 'usuario_id', 'historial_json', 'id_reserva', 'requiere_humano', 'updated_at')->get();
        if ($convs->isEmpty()) {
            return ['ok' => true, 'nuevos' => 0, 'mensaje' => 'No hay conversaciones en el período.'];
        }
        $bloques = [];
        $total = 0;
        foreach ($convs as $c) {
            $et = self::etapa($c);
            $lineas = [];
            foreach (self::transcripcion($c->id) as $t) {
                $quien = ['cliente' => 'CLIENTE', 'bot' => 'BOT', 'equipo' => 'EQUIPO', 'sistema' => 'SISTEMA'][$t['de']];
                $lineas[] = $quien . ': ' . mb_substr(preg_replace('/\s+/u', ' ', $t['texto']), 0, $t['de'] === 'sistema' ? 220 : 500);
            }
            $b = "### Conversación #{$c->id} — resultado: " . self::ETAPAS[$et['etapa']]
                . ($et['derivada'] ? ', derivada/atendida por el equipo' : '') . ($et['abandonada'] ? ', ABANDONADA' : '') . "\n"
                . implode("\n", $lineas);
            $b = mb_substr($b, 0, 3500);
            if ($total + mb_strlen($b) > 60000) {
                break;
            }
            $total += mb_strlen($b);
            $bloques[] = $b;
        }

        $previos = DB::table('bot_aprendizajes')->orderBy('id', 'desc')->limit(80)->get(['titulo', 'estado'])
            ->map(function ($r) { return '- [' . $r->estado . '] ' . $r->titulo; })->implode("\n");
        $reglas = '';
        try {
            $reglas = (new BotPromptService())->getSystemPrompt([], [], '');
            $reglas = mb_substr($reglas, 0, 18000);
        } catch (\Throwable $e) {
        }

        $sistema = "Eres analista de calidad del bot de WhatsApp de Botacura (spa de día en el Cajón del Maipo, Chile). "
            . "Revisas conversaciones reales para mejorar cómo el bot toma reservas. Responde SOLO con JSON válido.";
        $usuario = "Estas son las instrucciones actuales del bot (resumen):\n<<<\n{$reglas}\n>>>\n\n"
            . "Aprendizajes ya registrados (no los repitas):\n" . ($previos ?: '- (ninguno)') . "\n\n"
            . "Conversaciones de los últimos {$dias} días (SISTEMA = resultados internos; EQUIPO = respuestas de una persona del equipo):\n\n"
            . implode("\n\n", $bloques) . "\n\n"
            . "Propón entre 0 y 8 aprendizajes concretos y útiles. Tipos:\n"
            . "- \"flujo\": dónde el bot pierde o confunde al cliente (preguntas de más, pasos repetidos, cortes) y cómo corregirlo.\n"
            . "- \"dato\": información que el EQUIPO respondió y el bot no sabía, que sirve para todos los clientes.\n"
            . "- \"respuesta\": una pregunta frecuente y cómo debería responderla el bot.\n"
            . "- \"tono\": ajustes de estilo concretos.\n"
            . "- \"problema\": errores del bot o del sistema (montos, fechas, políticas mal dichas) que una persona debe revisar.\n"
            . "Reglas: NO conviertas en regla excepciones puntuales del equipo (descuentos, favores, cupos especiales); "
            . "no inventes precios ni políticas; si algo contradice las políticas, márcalo como \"problema\". "
            . "Cada propuesta debe poder agregarse tal cual a las instrucciones del bot (una o dos frases, en español de Chile, en imperativo).\n"
            . "Formato: {\"aprendizajes\":[{\"tipo\":\"flujo|dato|respuesta|tono|problema\",\"titulo\":\"máx 80 caracteres\","
            . "\"propuesta\":\"texto para el bot\",\"motivo\":\"por qué, en una frase\",\"conversaciones\":[ids],\"prioridad\":\"alta|media|baja\"}]}";

        $modelo = env('ANTHROPIC_MODEL_ANALISIS') ?: config('services.anthropic.model', 'claude-haiku-4-5-20251001');
        try {
            $client = new GuzzleClient(['timeout' => 180, 'http_errors' => false]);
            $res = $client->post('https://api.anthropic.com/v1/messages', [
                'headers' => ['x-api-key' => config('services.anthropic.key'), 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'],
                'json'    => ['model' => $modelo, 'max_tokens' => 4000, 'system' => $sistema, 'messages' => [['role' => 'user', 'content' => $usuario]]],
            ]);
            if ($res->getStatusCode() >= 300) {
                Log::error('[BotAprendizaje] Error IA', ['status' => $res->getStatusCode(), 'body' => substr((string) $res->getBody(), 0, 300)]);
                return ['ok' => false, 'nuevos' => 0, 'mensaje' => 'La IA respondió con error ' . $res->getStatusCode() . '.'];
            }
            $txt = json_decode((string) $res->getBody(), true)['content'][0]['text'] ?? '';
            $a = strpos($txt, '{');
            $b = strrpos($txt, '}');
            $data = ($a !== false && $b !== false) ? json_decode(substr($txt, $a, $b - $a + 1), true) : null;
            if (!is_array($data) || !isset($data['aprendizajes'])) {
                return ['ok' => false, 'nuevos' => 0, 'mensaje' => 'No se pudo leer la respuesta de la IA.'];
            }
        } catch (\Throwable $e) {
            Log::error('[BotAprendizaje] Excepción IA: ' . $e->getMessage());
            return ['ok' => false, 'nuevos' => 0, 'mensaje' => 'Error al llamar a la IA: ' . $e->getMessage()];
        }

        $nuevos = 0;
        foreach (array_slice($data['aprendizajes'], 0, 8) as $ap) {
            $titulo = mb_substr(trim((string) ($ap['titulo'] ?? '')), 0, 120);
            $prop   = trim((string) ($ap['propuesta'] ?? ''));
            if ($titulo === '' || $prop === '' || DB::table('bot_aprendizajes')->where('titulo', $titulo)->exists()) {
                continue;
            }
            $tipo = in_array($ap['tipo'] ?? '', ['flujo', 'dato', 'respuesta', 'tono', 'problema'], true) ? $ap['tipo'] : 'flujo';
            DB::table('bot_aprendizajes')->insert([
                'tipo'       => $tipo,
                'titulo'     => $titulo,
                'propuesta'  => mb_substr($prop, 0, 2000),
                'motivo'     => mb_substr(trim((string) ($ap['motivo'] ?? '')), 0, 1000),
                'evidencia'  => json_encode(array_values(array_filter(array_map('intval', (array) ($ap['conversaciones'] ?? []))))),
                'prioridad'  => in_array($ap['prioridad'] ?? '', ['alta', 'media', 'baja'], true) ? $ap['prioridad'] : 'media',
                'estado'     => 'sugerido',
                'activo'     => 0,
                'origen'     => 'ia',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $nuevos++;
        }
        return ['ok' => true, 'nuevos' => $nuevos, 'mensaje' => "Análisis listo: {$nuevos} sugerencia(s) nueva(s) de " . count($bloques) . ' conversaciones.'];
    }
}
