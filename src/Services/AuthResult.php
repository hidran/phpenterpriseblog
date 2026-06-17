<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?User $user = null,
    ) {
    }

    public static function failure(string $message): self
    {
        return new self(false, $message, null);
    }

    public static function success(string $message, ?User $user = null): self
    {
        return new self(true, $message, $user);
    }
}
