<?php

namespace App\Http\Middleware;

use Closure;

class ApiKeyMiddleware
{
    public function handle($request, Closure $next)
    {
        $key = config('app.laravel_api_key');

        if (!$key) {
            return response()->json(['error' => 'API key not configured'], 500);
        }

        $provided = $request->header('X-API-KEY') ?? $request->query('api_key');

        if (!$provided || !hash_equals($key, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
