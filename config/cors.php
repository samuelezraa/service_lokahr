<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 🌟 Tambahkan URL domain/port frontend Anda di sini
  'allowed_origins' => [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:4173',
    'http://127.0.0.1:4173',
],

    'allowed_origins_patterns' => [
        '#^http://192\.168\.\d+\.\d+:5173$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

   'max_age' => 86400,

    'supports_credentials' => true,

];