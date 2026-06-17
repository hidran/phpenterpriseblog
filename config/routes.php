<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\PostController;

return [
    'GET' => [
        '/'                => [PostController::class, 'index'],
        'posts'            => [PostController::class, 'index'],
        'posts/create'     => [PostController::class, 'create'],
        'posts/:id'        => [PostController::class, 'show'],
        'posts/:id/edit'   => [PostController::class, 'edit'],
        'auth/login'       => [AuthController::class, 'showLogin'],
        'auth/signup'      => [AuthController::class, 'showSignup'],
        // 'healthz' => [App\Controllers\HealthController::class, 'check'], // Task 4.3: uncomment when HealthController is added
    ],
    'POST' => [
        'posts'                  => [PostController::class, 'save'],
        'posts/:id'              => [PostController::class, 'save'],
        'posts/:id/delete'       => [PostController::class, 'delete'],
        'posts/:id/comments'     => [PostController::class, 'saveComment'],
        'auth/login'             => [AuthController::class, 'login'],
        'auth/signup'            => [AuthController::class, 'signup'],
        'auth/logout'            => [AuthController::class, 'logout'],
    ],
];
