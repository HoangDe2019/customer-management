<?php
// config/sanctum.php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    'expiration' => null, // Tokens never expire

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\AdminMiddleware::class,
        'encrypt_cookies' => App\Http\Middleware\AdminMiddleware::class,
    ],
];
