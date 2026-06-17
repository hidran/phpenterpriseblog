<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final readonly class AuthResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?User $user = null,
    ) {
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }

    public static function success(string $message, ?User $user = null): self
    {
        return new self(true, $message, $user);
    }
}
