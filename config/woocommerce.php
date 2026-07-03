<?php

return [
    'webhook_secret'   => env('WOOCOMMERCE_WEBHOOK_SECRET', ''),
    'consumer_key'     => env('WOOCOMMERCE_CONSUMER_KEY', ''),
    'consumer_secret'  => env('WOOCOMMERCE_CONSUMER_SECRET', ''),
    'store_url'        => env('WOOCOMMERCE_STORE_URL', ''),
    'system_user_id'   => env('WOOCOMMERCE_SYSTEM_USER_ID', 1),
    'api_key'          => env('LARAVEL_API_KEY', ''),

    // WordPress Application Password para subir imágenes al Media Library
    'wp_user'         => env('WP_APP_USER', ''),
    'wp_app_password' => env('WP_APP_PASSWORD', ''),

    // URL base donde están las imágenes de servicios (subidas por FTP).
    // Convención de nombres: {primer_segmento_slug}-N.ext  (ej: masaje-1.jpeg, masaje-2.jpeg…)
    // Al subir nuevas imágenes por FTP se detectan automáticamente (cache 1h).
    'service_image_base_url' => env('WP_SERVICE_IMAGE_BASE_URL', 'https://botacura.cl/wp-content/uploads/2026/05'),

    // Endpoint WP para limpiar caché de fechas disponibles (llamado desde CalendarioController)
    'wp_cache_clear_url'   => env('WP_CACHE_CLEAR_URL', ''),
    'wp_cache_clear_token' => env('WP_CACHE_CLEAR_TOKEN', ''),

    // Slugs de programas que SÍ están disponibles para Gift Card
    'gift_card_slugs' => ['full-day', 'botacura-full', 'caviahue-2'],
];