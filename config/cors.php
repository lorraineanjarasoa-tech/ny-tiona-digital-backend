<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

   'allowed_origins' => [
    'https://frontend-ny-tiona-digital.vercel.app',
    'https://frontend-seven-lemon-qdpje2gw8k.vercel.app',
    'https://nytionadigital.mg',
    'https://www.nytionadigital.mg',
    'http://localhost:5173',
],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,
];