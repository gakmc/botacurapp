<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Valida la API key (header X-API-KEY o ?api_key=) contra LARAVEL_API_KEY.
 *
 * Uso: ->middleware('auth.apikey')            siempre exige la clave
 *      ->middleware('auth.apikey:iot')        exige la clave salvo que
 *                                             config('app.api_key_exigir.iot') sea false
 *
 * El modo permisivo (IOT_EXIGIR_API_KEY=false / EGRESOS_EXIGIR_API_KEY=false) es solo
 * transitorio, mientras se configura el cliente externo: deja pasar llamadas sin clave
 * válida, pero las registra en el log y las marca en la auditoría como "sin API key".
 */
class ApiKeyMiddleware
{
    public function handle($request, Closure $next, $ambito = null)
    {
        $key      = config('app.laravel_api_key');
        $provided = $request->header('X-API-KEY') ?? $request->query('api_key');
        $valida   = $key && $provided && hash_equals($key, $provided);

        if ($valida) {
            return $next($request);
        }

        $exigir = $ambito === null || (bool) config('app.api_key_exigir.' . $ambito, true);

        if ($exigir) {
            if (! $key) {
                return response()->json(['error' => 'API key not configured'], 500);
            }

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Modo permisivo transitorio
        Log::warning('[API key] Llamada sin API key válida aceptada (modo permisivo)', [
            'ambito' => $ambito,
            'ruta'   => $request->path(),
            'ip'     => $request->ip(),
        ]);
        app(AuditoriaService::class)->agregarDetalleActor('sin API key');

        return $next($request);
    }
}
