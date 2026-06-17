<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly string $roletype,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:       (int) $row['id'],
            username: (string) $row['username'],
            email:    (string) $row['email'],
            password: (string) $row['password'],
            roletype: (string) ($row['roletype'] ?? 'user'),
        );
    }
}
