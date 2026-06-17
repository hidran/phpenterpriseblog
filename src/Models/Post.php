<?php

declare(strict_types=1);

namespace App\Models;

final class Post
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $message,
        public readonly int $userId,
        public readonly string $datecreated,
        public readonly string $email,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:          (int) $row['id'],
            title:       (string) $row['title'],
            message:     (string) $row['message'],
            userId:      (int) $row['user_id'],
            datecreated: (string) $row['datecreated'],
            email:       (string) $row['email'],
        );
    }
}
