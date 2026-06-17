<?php

declare(strict_types=1);

namespace App\Models;

final class Comment
{
    public function __construct(
        public readonly int $id,
        public readonly int $postId,
        public readonly ?int $userId,
        public readonly string $comment,
        public readonly string $email,
        public readonly string $datecreated,
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
