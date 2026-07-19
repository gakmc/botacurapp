<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WpCacheService
{
    public function clearFechasCache(): void
    {
        $url   = config('woocommerce.wp_cache_clear_url');
        $token = config('woocommerce.wp_cache_clear_token');

        if (!$url || !$token) {
            return;
        }

        try {
            (new Client(['timeout' => 5]))->post($url, [
                'headers' => ['X-Cache-Token' => $token],
            ]);
        } catch (\Exception $e) {
            // No crítico — el caché expirará solo en 30 min
            Log::warning('[WP-Cache] No se pudo limpiar caché de fechas: ' . $e->getMessage());
        }
    }
}
