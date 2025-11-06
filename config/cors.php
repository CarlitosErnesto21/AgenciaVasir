<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
<<<<<<< HEAD
        'http://192.168.1.5:5173', // Ajusta según tu red local
        'http://localhost:8000',
        'http://192.168.1.5:8000', // Ajusta según tu red local
=======
        'http://192.168.1.38:5173', // Ajusta según tu red local
        'http://localhost:8000',
        'http://192.168.1.38:8000', // Ajusta según tu red local
>>>>>>> 73b45bc16e27aa147934ef273eb67ba3644e61fe
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
