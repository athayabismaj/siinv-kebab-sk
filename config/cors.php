<?php

$allowedOrigins = array_filter(array_map(
    static fn (string $origin): string => trim($origin),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
));

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => array_filter(array_map(
        static fn (string $pattern): string => trim($pattern),
        explode(',', (string) env('CORS_ALLOWED_ORIGINS_PATTERNS', ''))
    )),

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-Idempotency-Key',
    ],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 0),

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),
];
