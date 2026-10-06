<?php

namespace App\Http\Controllers;

use App\BotMensajePendiente;
use App\BotPasoFlujo;
use App\BotSeccionTexto;
use App\TipoMasaje;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

/**
 * Vista de edición del contenido del bot de WhatsApp: perfil de vendedor,
 * info del recinto (horarios/políticas/recomendaciones) y el guion paso a paso.
 * El catálogo de masajes se muestra en modo solo-lectura (se administra desde
 * Masajes > Valores; acá solo se ve un espejo de lo que el bot va a usar).
 *
 * Además: interruptor para activar/desactivar el bot, ajustes de alertas y la
 * cola de mensajes que llegaron mientras el bot estaba apagado.
 *
 * Todo lo que se guarda acá es leído en vivo por BotPromptService cada vez que
 * el bot arma el prompt — no requiere deploy para tomar efecto.
 */
class BotConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:' . config('app.admin_role'));
    }

    public function index()
    {
        $perfil  = BotSeccionTexto::categoria('perfil')->get();
        $recinto = BotSeccionTexto::categoria('recinto')->get();
        $pasos   = BotPasoFlujo::orderBy('orden')->get();

        $masajes = TipoMasaje::activos()
            ->with(['categoria', 'precios' => function ($q) {
                $q->orderBy('duracion_minutos');
            }])
            ->whereHas('precios')
            ->get()
            ->groupBy(function ($t) {
                return $t->categoria->nombre ?? 'Otros';
            });

        $botActivo      = BotSeccionTexto::where('clave', 'bot_activo')->value('contenido') !== '0';
        $alertaCorreo   = (string) BotSeccionTexto::where('clave', 'alerta_correo')->value('contenido');
        $alertaTelefono = (string) BotSeccionTexto::where('clave', 'alerta_telefono')->value('contenido');

        // Pendientes agrupados por teléfono (una fila por cliente)
        $pendientes = BotMensajePendiente::pendientes()->orderBy('created_at')->get()
            ->groupBy('telefono')
            ->map(function ($msgs, $tel) {
                $ultimo = $msgs->last();
                return (object) [
                    'telefono'    => $tel,
                    'nombre'      => $ultimo->nombre,
                    'mensajes'    => $msgs,
                    'ultimo_at'   => $ultimo->created_at,
                    'en_ventana'  => $ultimo->created_at->gt(Carbon::now()->subHours(24)),
                ];
            })
            ->sortByDesc('ultimo_at')
            ->values();

        return view('themes.backoffice.pages.bot-configuracion.index', compact(
            'perfil', 'recinto', 'pasos', 'masajes', 'botActivo', 'alertaCorreo', 'alertaTelefono', 'pendientes'
        ));
    }

    /**
     * Actualiza en bloque el contenido de las secciones de texto (perfil o recinto).
     * Espera $request->contenido = [clave => texto, ...]
     */
    public function updateSecciones(Request $request)
    {
        $request->validate([
            'contenido' => 'required|array',
            'contenido.*' => 'nullable|string',
        ]);

        foreach ($request->input('contenido') as $clave => $texto) {
            BotSeccionTexto::where('clave', $clave)->update([
                'contenido' => $texto,
                'updated_by' => auth()->id(),
            ]);
        }

        Alert::success('Éxito', 'Información actualizada. El bot ya está usando el nuevo texto.')->showConfirmButton();
        return redirect()->route('backoffice.bot-configuracion.index');
    }

    /**
     * Actualiza en bloque los pasos del guion del bot.
     * Espera $request->titulo[id], $request->instrucciones[id], $request->activo[id] (checkbox)
     */
    public function updatePasos(Request $request)
    {
        $request->validate([
            'titulo' => 'required|array',
            'instrucciones' => 'required|array',
        ]);

        $activos = $request->input('activo', []); // ids marcados como activos

        foreach ($request->input('titulo') as $id => $titulo) {
            BotPasoFlujo::where('id', $id)->update([
                'titulo' => $titulo,
                'instrucciones' => $request->input('instrucciones')[$id] ?? '',
                'activo' => in_array((string) $id, $activos),
                'updated_by' => auth()->id(),
            ]);
        }

        Alert::success('Éxito', 'Guion del bot actualizado.')->showConfirmButton();
        return redirect()->route('backoffice.bot-configuracion.index');
    }

    /**
     * Interruptor del bot + datos de alerta.
     */
    public function updateEstado(Request $request)
    {
        $request->validate([
            'alerta_correo'   => 'nullable|email',
            'alerta_telefono' => 'nullable|string|max:20',
        ]);

        $activo = $request->has('bot_activo') ? '1' : '0';
        $this->guardarAjuste('bot_activo', $activo);
        if ($request->has('alerta_correo')) {
            $this->guardarAjuste('alerta_correo', (string) $request->input('alerta_correo'));
        }
        if ($request->has('alerta_telefono')) {
            $this->guardarAjuste('alerta_telefono', preg_replace('/\D/', '', (string) $request->input('alerta_telefono')));
        }

        $pend = BotMensajePendiente::pendientes()->distinct('telefono')->count('telefono');
        if ($activo === '1') {
            $msg = 'Bot ACTIVADO.' . ($pend ? " Hay {$pend} conversación(es) pendiente(s) abajo." : '');
        } else {
            $msg = 'Bot DESACTIVADO. Los mensajes quedarán pendientes y recibirás una alerta.';
        }
        Alert::success('Listo', $msg)->showConfirmButton();
        return redirect()->route('backoffice.bot-configuracion.index');
    }

    /**
     * Responde con el bot (o marca como atendidas) las conversaciones pendientes seleccionadas.
     */
    public function procesarPendientes(Request $request)
    {
        $request->validate([
            'telefonos'   => 'required|array|min:1',
            'telefonos.*' => 'string',
            'accion'      => 'required|in:responder,atendido',
        ]);

        $telefonos = array_slice($request->input('telefonos'), 0, 10);

        if ($request->input('accion') === 'atendido') {
            BotMensajePendiente::pendientes()->whereIn('telefono', $telefonos)
                ->update(['estado' => 'atendido', 'respondido_at' => now()]);
            Alert::success('Listo', count($telefonos) . ' conversación(es) marcadas como atendidas.')->showConfirmButton();
            return redirect()->route('backoffice.bot-configuracion.index');
        }

        if (BotSeccionTexto::where('clave', 'bot_activo')->value('contenido') === '0') {
            Alert::warning('Bot desactivado', 'Activa el bot antes de responder los pendientes.')->showConfirmButton();
            return redirect()->route('backoffice.bot-configuracion.index');
        }

        @set_time_limit(300);
        $ok = 0; $fuera = 0; $error = 0;

        foreach ($telefonos as $i => $tel) {
            $msgs = BotMensajePendiente::pendientes()->where('telefono', $tel)->orderBy('created_at')->get();
            if ($msgs->isEmpty()) {
                continue;
            }
            $ultimo = $msgs->last();
            if ($ultimo->created_at->lte(Carbon::now()->subHours(24))) {
                $fuera++; // WhatsApp no permite responder con texto libre pasadas 24 h
                continue;
            }

            $horas = max(1, (int) round($msgs->first()->created_at->diffInMinutes(now()) / 60));
            $texto = "[Contexto interno: estos mensajes llegaron hace aprox. {$horas} hora(s), mientras no había atención automática. "
                   . "Empieza disculpándote brevemente por la demora y responde todo lo que pregunta.]\n"
                   . $msgs->pluck('mensaje')->implode("\n");

            try {
                if ($i > 0) {
                    sleep(2);
                }
                $client = new GuzzleClient(['timeout' => 40, 'http_errors' => false]);
                $res = $client->post(url('/api/bot-ai/message'), [
                    'headers' => [
                        'X-Bot-Secret' => config('services.bot.secret'),
                        'X-Bot-Token'  => env('BOT_API_TOKEN'),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'telefono' => $tel,
                        'mensaje'  => $texto,
                        'nombre'   => $ultimo->nombre ?: 'Cliente',
                    ],
                ]);
                $data = json_decode((string) $res->getBody(), true) ?? [];
                $respuesta = $data['mensaje'] ?? null;

                if ($respuesta && $this->enviarWhatsApp($tel, $respuesta, $ultimo->phone_number_id)) {
                    BotMensajePendiente::pendientes()->where('telefono', $tel)
                        ->update(['estado' => 'respondido', 'respondido_at' => now()]);
                    $ok++;
                } else {
                    $error++;
                    Log::warning('[Bot pendientes] Sin respuesta para ' . $tel, ['status' => $res->getStatusCode()]);
                }
            } catch (\Exception $e) {
                $error++;
                Log::error('[Bot pendientes] ' . $tel . ': ' . $e->getMessage());
            }
        }

        $resumen = "Respondidas: {$ok}.";
        if ($fuera) $resumen .= " Fuera de plazo (más de 24 h, responder a mano): {$fuera}.";
        if ($error) $resumen .= " Con error (revisar log): {$error}.";
        Alert::info('Pendientes procesados', $resumen)->showConfirmButton();
        return redirect()->route('backoffice.bot-configuracion.index');
    }

    private function guardarAjuste(string $clave, string $valor)
    {
        BotSeccionTexto::where('clave', $clave)->update([
            'contenido'  => $valor,
            'updated_by' => auth()->id(),
        ]);
    }

    private function enviarWhatsApp(string $telefono, string $texto, ?string $phoneNumberId): bool
    {
        $pnid    = $phoneNumberId ?: env('META_PHONE_NUMBER_ID');
        $version = env('META_API_VERSION', 'v21.0');
        $token   = env('META_WHATSAPP_TOKEN');
        if (!$pnid || !$token) {
            Log::error('[Bot pendientes] Falta phone_number_id o token de Meta');
            return false;
        }
        $client = new GuzzleClient(['timeout' => 15, 'http_errors' => false]);
        $res = $client->post("https://graph.facebook.com/{$version}/{$pnid}/messages", [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'json'    => [
                'messaging_product' => 'whatsapp',
                'to'                => $telefono,
                'type'              => 'text',
                'text'              => ['body' => $texto],
            ],
        ]);
        if ($res->getStatusCode() >= 300) {
            Log::error('[Bot pendientes] Meta respondió ' . $res->getStatusCode() . ': ' . substr((string) $res->getBody(), 0, 300));
            return false;
        }
        return true;
    }
}
