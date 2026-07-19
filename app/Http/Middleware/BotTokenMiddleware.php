<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BotTokenMiddleware
{
    /**
     * Valida que el request del chatbot incluya el token correcto
     * en el header X-Bot-Token.
     *
     * Configura el token en .env:
     *   BOT_API_TOKEN=tu_token_secreto_largo
     *
     * n8n debe enviar en cada request:
     *   Header: X-Bot-Token: <valor de BOT_API_TOKEN>
     */
    public function handle(Request $request, Closure $next)
    {
        $tokenEnviado   = $request->header('X-Bot-Token');
        $tokenEsperado  = config('app.bot_api_token');

        if (empty($tokenEsperado)) {
            \Illuminate\Support\Facades\Log::error('[Bot] BOT_API_TOKEN no configurado en .env');
            return response()->json(['error' => 'Configuración incompleta en el servidor'], 500);
        }

        if (empty($tokenEnviado) || !hash_equals($tokenEsperado, $tokenEnviado)) {
            \Illuminate\Support\Facades\Log::warning('[Bot] Token inválido. IP: ' . $request->ip());
            return response()->json(['error' => 'No autorizado'], 401);
        }

        return $next($request);
    }
}
