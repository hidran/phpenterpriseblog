<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Post
{
    public function __construct(
        public int $id,
        public string $title,
        public string $message,
        public int $userId,
        public string $datecreated,
        public string $email,
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
