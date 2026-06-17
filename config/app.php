<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'env'   => Env::string('APP_ENV', 'local'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url'   => Env::string('APP_URL', 'http://localhost:8080'),
    'paths' => [
        'views' => dirname(__DIR__) . '/resources/views',
    ],
];
