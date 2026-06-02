<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for cross-origin resource sharing. The frontend (Next.js)
    | runs on a different port/domain, so we need to allow these requests.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', env('APP_ENV') === 'production' ? null : 'http://localhost:3000'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 600,

    // The frontend now authenticates via HttpOnly cookies, so the browser
    // must be allowed to send credentials on cross-origin requests. This
    // requires `allowed_origins` to be an explicit allowlist (no '*').
    'supports_credentials' => true,

];
