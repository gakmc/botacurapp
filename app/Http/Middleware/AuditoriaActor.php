<?php

namespace App\Http\Middleware;

use App\Auditoria;
use App\Services\AuditoriaService;
use Closure;

/**
 * Define quién figura como actor en la auditoría para rutas sin usuario logueado.
 *
 *   ->middleware('auditoria:sistema,WooCommerce')  → "Sistema (WooCommerce)"
 *   ->middleware('auditoria:omitir')               → no se audita (canales del bot)
 */
class AuditoriaActor
{
    public function handle($request, Closure $next, $modo = 'sistema', ...$detalle)
    {
        $servicio = app(AuditoriaService::class);

        if ($modo === 'omitir') {
            $servicio->omitir();
        } else {
            $servicio->establecerActor(null, Auditoria::ACTOR_SISTEMA, implode(', ', $detalle) ?: null);
        }

        return $next($request);
    }
}
