<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'https://sertecapp-tecnicos.pages.dev',
        'https://demo.pendziuch.com',
    ],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.pages\.dev$/',
        '/^https:\/\/.*\.pendziuch\.com$/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
