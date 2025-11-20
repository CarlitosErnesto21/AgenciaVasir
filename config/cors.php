<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://192.168.1.5:5173',
        'http://localhost:8000',
        'http://192.168.1.5:8000',
        'http://localhost:8080',
        'http://192.168.1.5:8080',
        'http://127.0.0.1:8080',
        'https://vasir.onrender.com'
    ],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.railway\.app$/',
        '/^https:\/\/.*\.render\.com$/',
        '/^https:\/\/.*\.vercel\.app$/'
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
