<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| The StatKick SPA (Vite) calls this API from a different origin and
| authenticates with Sanctum Bearer tokens, so no cookies/credentials are
| needed. Extra origins (e.g. a deployed frontend) can be added through
| CORS_ALLOWED_ORIGINS as a comma-separated list.
|
*/

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map('trim', [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        ...explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
    ]))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
