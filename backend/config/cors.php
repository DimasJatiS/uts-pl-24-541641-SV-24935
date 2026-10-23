<?php

/*
| Di Docker, frontend dan backend berada di origin yang sama (nginx mem-proxy /api),
| jadi CORS praktis hanya dipakai saat development dengan dev server Vite.
| Origin dibatasi lewat CORS_ALLOWED_ORIGINS, bukan "*".
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 600,

    // Token dikirim lewat header Authorization, bukan cookie.
    'supports_credentials' => false,

];
