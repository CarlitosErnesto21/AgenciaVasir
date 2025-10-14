<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://192.168.1.34:5173',
        'http://localhost:8000',
        'http://192.168.1.34:8000',
        'https://web-production-32cf.up.railway.app'
    ], // Ajusta según tu frontend y backend
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
