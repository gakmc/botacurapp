<?php

namespace App\Http\Middleware;

use Closure;

class CotizadorToken
{
    public function handle($request, Closure $next)
    {
        $esperado = config('services.cotizador.token');
        $recibido = $request->bearerToken() ?: $request->header('X-Cotizador-Token');

        if (empty($esperado) || empty($recibido) || !hash_equals($esperado, $recibido)) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        return $next($request);
    }
}
