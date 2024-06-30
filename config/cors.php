<?php
// THIS IS CORS.PHP LARAVEL CONFIG
return [
    'paths' => ['api/*', '/api/auth/login', '/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
