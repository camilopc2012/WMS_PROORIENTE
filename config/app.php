<?php
/**
 * Application Configuration
 */

return [
    'env'       => getenv('APP_ENV') ?: 'development',
    'debug'     => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
    'url'       => getenv('APP_URL') ?: 'http://localhost/Prooriente',
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'change_this_secret',
        'expiry' => (int)(getenv('JWT_EXPIRY') ?: 28800),
    ],
    'uploads' => [
        'productos' => __DIR__ . '/../public/uploads/productos/',
    ],
];
