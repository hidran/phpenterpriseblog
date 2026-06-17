<?php

declare(strict_types=1);

namespace App\Models;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public string $password,
        public string $roletype,
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
