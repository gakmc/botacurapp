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

    // Cupos máximos de ubicaciones por espacio_tipo para WooCommerce.
    // Distribución real: 9 estaciones (3+3+3) + 6 terrazas + 4 reposeras = 19 ubicaciones.
    'wc_espacios' => [
        'estacion_economico'  => (int) env('WC_CUPO_ESTACION_ECONOMICO', 3),
        'estacion_intermedio' => (int) env('WC_CUPO_ESTACION_INTERMEDIO', 3),
        'estacion_full'       => (int) env('WC_CUPO_ESTACION_FULL', 3),
        'terraza'             => (int) env('WC_CUPO_TERRAZA', 6),
        'reposera'            => (int) env('WC_CUPO_REPOSERA', 4),
    ],

    // Personas que caben en una sola ubicación de este tipo.
    // Tipos sin entrada aquí usan COUNT simple (1 reserva = 1 ubicación).
    //   terraza : ceil(personas/6) terrazas por reserva   (max 6 personas/terraza)
    //   reposera: ceil(personas/2) pares por reserva      (2 reposeras/ubicación, min 2 personas)
    'wc_personas_por_ubicacion' => [
        'terraza'  => (int) env('WC_PERSONAS_TERRAZA', 6),
        'reposera' => (int) env('WC_PERSONAS_REPOSERA', 2),
    ],

    // Slugs de programas que SÍ están disponibles para Gift Card
    'gift_card_slugs' => ['full-day', 'botacura-full', 'caviahue-2'],

    // Temporada invierno (temporal): permite ingresar fecha_uso/validez_hasta a mano
    // al crear/editar una Gift Card. En false vuelve al cálculo automático de siempre.
    'gift_card_fecha_manual' => env('GIFTCARD_FECHA_MANUAL', false),
];