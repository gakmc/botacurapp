<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Conecta un número que ya usa la app WhatsApp Business a la API de WhatsApp (coexistencia),
 * con el Registro insertado (Embedded Signup) de Meta. El número sigue funcionando en la app del
 * celular y Bot-Acura responde por la API. Requiere en .env: META_APP_ID, META_APP_SECRET y
 * META_ES_CONFIG_ID (configuración de "Inicio de sesión con Facebook para empresas").
 */
class WhatsAppConectarController extends Controller
{
    const VER = 'v25.0';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('whatsapp.conectar', [
            'appId'    => env('META_APP_ID'),
            'configId' => env('META_ES_CONFIG_ID'),
            'faltan'   => array_values(array_filter(['META_APP_ID', 'META_APP_SECRET', 'META_ES_CONFIG_ID'], function ($k) {
                return !env($k);
            })),
            'ver'      => self::VER,
        ]);
    }

    public function finalizar(Request $request)
    {
        $request->validate(['pagina' => 'nullable|string', 'code' => 'required|string', 'waba_id' => 'nullable|string', 'phone_number_id' => 'nullable|string', 'evento' => 'nullable|string']);
        $pasos = [];
        $http  = new GuzzleClient(['timeout' => 30, 'http_errors' => false]);
        $g     = 'https://graph.facebook.com/' . self::VER . '/';

        // 1) Canjear el código (dura 30 s) por el token del negocio
        // Meta valida el redirect_uri del diálogo: se prueba sin él, con la página actual y con la raíz del sitio.
        $r = [];
        foreach ([null, (string) $request->input('pagina'), url('/') . '/', ''] as $redir) {
            $q = ['client_id' => env('META_APP_ID'), 'client_secret' => env('META_APP_SECRET'), 'code' => $request->code];
            if ($redir !== null) {
                $q['redirect_uri'] = $redir;
            }
            $r = json_decode((string) $http->get($g . 'oauth/access_token', ['query' => $q])->getBody(), true) ?: [];
            if (!empty($r['access_token'])) {
                break;
            }
        }
        if (!empty($r['access_token'])) {
            $tok = $r['access_token'];
            $pasos[] = ['Código canjeado por el token del negocio', true, ''];
        } else {
            // Sin el canje igual se puede terminar con el token permanente (el usuario del sistema administra la cuenta).
            Log::warning('[WhatsApp conectar] No se pudo canjear el código; se usa el token permanente', ['error' => $r['error'] ?? $r]);
            $tok = env('META_WHATSAPP_TOKEN');
            $pasos[] = ['No se pudo canjear el código (se sigue con el token permanente)', false, $r['error']['message'] ?? ''];
        }
        $auth = ['Authorization' => 'Bearer ' . $tok];

        $waba  = (string) $request->waba_id;
        $phone = (string) $request->phone_number_id;

        // 2) Suscribir la app a la cuenta (para que lleguen los mensajes al webhook)
        if ($waba) {
            $s = json_decode((string) $http->post($g . $waba . '/subscribed_apps', ['headers' => $auth])->getBody(), true) ?: [];
            $pasos[] = ['App suscrita a la cuenta de WhatsApp ' . $waba, !empty($s['success']), $s['error']['message'] ?? ''];
        }

        // 3) Estado del número
        $info = [];
        if ($phone) {
            $info = json_decode((string) $http->get($g . $phone, ['headers' => $auth, 'query' => [
                'fields' => 'display_phone_number,verified_name,is_on_biz_app,platform_type,quality_rating',
            ]])->getBody(), true) ?: [];
            $pasos[] = ['Número ' . ($info['display_phone_number'] ?? $phone) . ' (' . ($info['verified_name'] ?? '?') . ')',
                empty($info['error']), $info['error']['message'] ?? ('en app: ' . (!empty($info['is_on_biz_app']) ? 'sí' : 'no') . ' · plataforma: ' . ($info['platform_type'] ?? '?'))];
        }

        // 4) Coexistencia: sincronizar contactos e historial (una sola vez, dentro de 24 horas)
        if ($phone && ($request->evento === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING' || !empty($info['is_on_biz_app']))) {
            foreach (['smb_app_state_sync' => 'Contactos sincronizados', 'history' => 'Historial de chats (6 meses) solicitado'] as $tipo => $txt) {
                $x = json_decode((string) $http->post($g . $phone . '/smb_app_data', ['headers' => $auth, 'json' => [
                    'messaging_product' => 'whatsapp', 'sync_type' => $tipo,
                ]])->getBody(), true) ?: [];
                $pasos[] = [$txt, empty($x['error']), $x['error']['message'] ?? (isset($x['request_id']) ? 'request_id ' . $x['request_id'] : '')];
                Log::info('[WhatsApp conectar] smb_app_data ' . $tipo, ['phone' => $phone, 'respuesta' => $x]);
            }
        }

        Log::info('[WhatsApp conectar] Terminado', ['waba' => $waba, 'phone' => $phone, 'evento' => $request->evento, 'pasos' => $pasos]);
        return response()->json(['ok' => true, 'pasos' => $pasos, 'phone_number_id' => $phone, 'waba_id' => $waba,
            'numero' => $info['display_phone_number'] ?? null]);
    }
}
