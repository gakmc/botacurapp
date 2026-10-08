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
        $reglas  = BotSeccionTexto::categoria('reglas')->orderBy('orden')->get();
        $parametros = BotSeccionTexto::categoria('parametros')->orderBy('orden')->get();
        $fotos = [];
        foreach (\App\Services\BotUbicacionesService::tiposFoto() as $tipo => $d) {
            $fotos[$tipo] = (object) [
                'etiqueta' => $d[0],
                'url'      => \App\Services\BotUbicacionesService::fotoUrl($tipo),
                'texto'    => \App\Services\BotUbicacionesService::fotoTexto($tipo),
            ];
        }
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

        $apr = null;
        try {
            if (\Schema::hasTable('bot_aprendizajes')) {
                $aprDias = in_array((int) request('apr_dias'), [7, 14, 30, 90], true) ? (int) request('apr_dias') : 30;
                $apr = [
                    'metricas'       => \App\Services\BotAprendizajeService::metricas($aprDias),
                    'conversaciones' => \App\Services\BotAprendizajeService::conversacionesRecientes($aprDias, 30),
                    'sugeridos'      => \DB::table('bot_aprendizajes')->where('estado', 'sugerido')
                        ->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")->orderBy('id', 'desc')->get(),
                    'aprobados'      => \DB::table('bot_aprendizajes')->where('estado', 'aprobado')->orderBy('activo', 'desc')->orderBy('id', 'desc')->get(),
                ];
            }
        } catch (\Throwable $e) {
            \Log::error('[BotConfiguracion] Error cargando aprendizajes: ' . $e->getMessage());
            $apr = null;
        }

        $menu = (object) [
            'url'          => \App\Services\BotMenuService::url(),
            'actualizado'  => \App\Services\BotMenuService::actualizado(),
            'antiguo'      => \App\Services\BotMenuService::ruta() === \App\Services\BotMenuService::ANTIGUO,
            'titulo'       => \App\Services\BotMenuService::titulo(),
            'archivo'      => \App\Services\BotMenuService::nombreArchivo(),
            'texto'        => \App\Services\BotMenuService::texto(),
            'al_preguntar' => \App\Services\BotMenuService::enviarAlPreguntar(),
        ];

        return view('themes.backoffice.pages.bot-configuracion.index', compact(
            'perfil', 'recinto', 'reglas', 'parametros', 'fotos', 'apr', 'menu', 'pasos', 'masajes', 'botActivo', 'alertaCorreo', 'alertaTelefono', 'pendientes'
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

        // El equipo ya se hizo cargo (o pidió al bot responder): el bot vuelve a atender esos chats.
        \DB::table('bot_conversaciones')->whereIn('usuario_id', $telefonos)
            ->update(['humano_hasta' => null, 'requiere_humano' => 0]);

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

    /**
     * Fotos de ubicaciones que el bot envía por WhatsApp (una por tipo) y su texto.
     */
    public function updateFotos(Request $request)
    {
        $request->validate([
            'foto'    => 'nullable|array',
            'foto.*'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'texto'   => 'nullable|array',
            'texto.*' => 'nullable|string|max:1000',
        ]);
        $dir = public_path(\App\Services\BotUbicacionesService::DIR_FOTOS);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $subidas = 0;
        foreach (array_keys(\App\Services\BotUbicacionesService::tiposFoto()) as $tipo) {
            $f = $request->file('foto.' . $tipo);
            if ($f && $f->isValid()) {
                foreach (glob($dir . '/' . $tipo . '.*') ?: [] as $viejo) {
                    @unlink($viejo);
                }
                $ext = strtolower($f->getClientOriginalExtension() ?: 'jpg');
                $ext = $ext === 'jpeg' ? 'jpg' : $ext;
                $f->move($dir, $tipo . '.' . $ext);
                $subidas++;
            }
            if ($request->has('texto.' . $tipo)) {
                BotSeccionTexto::where('clave', 'foto_texto_' . $tipo)->update([
                    'contenido'  => (string) $request->input('texto.' . $tipo),
                    'updated_by' => auth()->id(),
                ]);
            }
        }
        Alert::success('Listo', $subidas ? "Se guardaron {$subidas} foto(s). El bot ya las usa." : 'Textos guardados.')->showConfirmButton();
        return redirect()->route('backoffice.bot-configuracion.index');
    }

    // ───────────────────────── Aprendizajes ─────────────────────────

    public function analizarAprendizajes(Request $request)
    {
        $dias = in_array((int) $request->input('dias'), [7, 14, 30], true) ? (int) $request->input('dias') : 7;
        $r = \App\Services\BotAprendizajeService::analizar($dias);
        if ($r['ok']) {
            Alert::success('Análisis listo', $r['mensaje'])->showConfirmButton();
        } else {
            Alert::error('No se pudo analizar', $r['mensaje'])->showConfirmButton();
        }
        return redirect(route('backoffice.bot-configuracion.index') . '#aprendizajes');
    }

    public function nuevoAprendizaje(Request $request)
    {
        $request->validate(['titulo' => 'required|string|max:150', 'propuesta' => 'required|string|max:2000']);
        \DB::table('bot_aprendizajes')->insert([
            'tipo' => 'dato', 'titulo' => $request->titulo, 'propuesta' => $request->propuesta,
            'estado' => 'aprobado', 'activo' => 1, 'origen' => 'equipo', 'prioridad' => 'media',
            'revisado_por' => auth()->id(), 'revisado_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        Alert::success('Listo', 'Aprendizaje agregado. El bot ya lo usa.')->showConfirmButton();
        return redirect(route('backoffice.bot-configuracion.index') . '#aprendizajes');
    }

    public function accionAprendizaje(Request $request, $id)
    {
        $request->validate(['accion' => 'required|in:aprobar,descartar,activar,desactivar,eliminar,guardar', 'propuesta' => 'nullable|string|max:2000']);
        $q = \DB::table('bot_aprendizajes')->where('id', (int) $id);
        $base = ['revisado_por' => auth()->id(), 'revisado_at' => now(), 'updated_at' => now()];
        if ($request->filled('propuesta')) {
            $base['propuesta'] = $request->propuesta;
        }
        switch ($request->accion) {
            case 'aprobar':
                $q->update($base + ['estado' => 'aprobado', 'activo' => 1]);
                $msg = 'Aprobado: el bot ya lo aplica.';
                break;
            case 'descartar':
                $q->update($base + ['estado' => 'descartado', 'activo' => 0]);
                $msg = 'Descartado. No se volverá a sugerir.';
                break;
            case 'activar':
                $q->update($base + ['activo' => 1]);
                $msg = 'Activado.';
                break;
            case 'desactivar':
                $q->update($base + ['activo' => 0]);
                $msg = 'Desactivado: el bot deja de usarlo.';
                break;
            case 'eliminar':
                $q->delete();
                $msg = 'Eliminado.';
                break;
            default:
                $q->update($base);
                $msg = 'Guardado.';
        }
        Alert::success('Listo', $msg)->showConfirmButton();
        return redirect(route('backoffice.bot-configuracion.index') . '#aprendizajes');
    }

    public function conversacionAprendizaje($id)
    {
        $c = \DB::table('bot_conversaciones')->where('id', (int) $id)->first(['id', 'usuario_id', 'nombre_cliente', 'updated_at']);
        if (!$c) {
            return response()->json(['ok' => false], 404);
        }
        return response()->json([
            'ok'       => true,
            'id'       => $c->id,
            'cliente'  => ($c->nombre_cliente ?: 'Cliente') . ' (+' . $c->usuario_id . ')',
            'fecha'    => (string) $c->updated_at,
            'mensajes' => \App\Services\BotAprendizajeService::transcripcion((int) $c->id),
        ]);
    }


    /**
     * Menú en PDF que envía el bot (tras el pago y cuando lo piden) + sus textos.
     */
    public function updateMenu(Request $request)
    {
        $request->validate([
            'menu_pdf'                 => 'nullable|file|mimes:pdf|max:20480',
            'menu_titulo'              => 'nullable|string|max:120',
            'menu_archivo_nombre'      => 'nullable|string|max:120',
            'menu_texto'               => 'nullable|string|max:1000',
        ]);
        $subido = false;
        $f = $request->file('menu_pdf');
        if ($f && $f->isValid()) {
            $dir = public_path(dirname(\App\Services\BotMenuService::ARCHIVO));
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $f->move($dir, basename(\App\Services\BotMenuService::ARCHIVO));
            $subido = true;
        }
        foreach (['menu_titulo', 'menu_archivo_nombre', 'menu_texto'] as $clave) {
            if ($request->has($clave)) {
                BotSeccionTexto::where('clave', $clave)->update(['contenido' => (string) $request->input($clave), 'updated_by' => auth()->id()]);
            }
        }
        BotSeccionTexto::where('clave', 'menu_enviar_al_preguntar')->update([
            'contenido' => $request->has('menu_enviar_al_preguntar') ? '1' : '0', 'updated_by' => auth()->id(),
        ]);
        Alert::success('Listo', $subido ? 'Menú actualizado. El bot ya envía el nuevo PDF.' : 'Ajustes del menú guardados.')->showConfirmButton();
        return redirect(route('backoffice.bot-configuracion.index') . '#menu');
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
