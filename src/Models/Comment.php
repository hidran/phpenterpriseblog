<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Comment
{
    public function __construct(
        public int $id,
        public int $postId,
        public ?int $userId,
        public string $comment,
        public string $email,
        public string $datecreated,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:          (int) $row['id'],
            postId:      (int) $row['post_id'],
            userId:      isset($row['user_id']) ? (int) $row['user_id'] : null,
            comment:     (string) $row['comment'],
            email:       (string) $row['email'],
            datecreated: (string) $row['datecreated'],
        );
    }
}
