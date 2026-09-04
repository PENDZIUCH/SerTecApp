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
        // Solo previews del proyecto propio en Cloudflare Pages, no cualquier *.pages.dev
        '/^https:\/\/[a-z0-9-]+\.sertecapp-tecnicos\.pages\.dev$/',
        '/^https:\/\/.*\.pendziuch\.com$/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
