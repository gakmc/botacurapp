<?php

namespace App\Services;

use App\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    /**
     * Envía el push a todos los dispositivos suscritos de los usuarios.
     * $opciones: TTL (segundos que el servicio de push guarda el aviso si el
     * celular no está conectado) y urgency.
     * Devuelve cuántos dispositivos lo recibieron; los fallos quedan en el log y
     * las suscripciones vencidas (el usuario desinstaló o revocó el permiso) se borran.
     */
    public function sendToUsers($users, array $payload, array $opciones = [])
    {
        $auth = [
            'VAPID' => [
                'subject'    => config('services.webpush.subject'),
                'publicKey'  => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);

        $subscriptions = PushSubscription::whereIn('user_id', $users->pluck('id'))->get();

        foreach ($subscriptions as $sub) {

            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
            ]);

            $webPush->queueNotification($subscription, json_encode($payload), $opciones + [
                'TTL' => 60,
                'urgency' => 'high',
            ]);
        }

        $enviados = 0;

        foreach ($webPush->flush() as $reporte) {
            if ($reporte->isSuccess()) {
                $enviados++;
                continue;
            }

            $endpoint = $reporte->getEndpoint();
            $sub = $subscriptions->firstWhere('endpoint', $endpoint);

            Log::warning('Push no entregado', [
                'titulo'  => $payload['title'] ?? null,
                'user_id' => optional($sub)->user_id,
                'motivo'  => $reporte->getReason(),
                'vencida' => $reporte->isSubscriptionExpired(),
            ]);

            if ($reporte->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $endpoint)->delete();
            }
        }

        if ($subscriptions->isEmpty()) {
            Log::info('Push sin dispositivos suscritos', [
                'titulo'   => $payload['title'] ?? null,
                'usuarios' => $users->pluck('id')->all(),
            ]);
        }

        return $enviados;
    }
}
