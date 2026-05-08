<?php

return [
    'webhook_secret'   => env('WOOCOMMERCE_WEBHOOK_SECRET', ''),
    'consumer_key'     => env('WOOCOMMERCE_CONSUMER_KEY', ''),
    'consumer_secret'  => env('WOOCOMMERCE_CONSUMER_SECRET', ''),
    'store_url'        => env('WOOCOMMERCE_STORE_URL', ''),
    'system_user_id'   => env('WOOCOMMERCE_SYSTEM_USER_ID', 3),
    'api_key'          => env('LARAVEL_API_KEY', ''),
];