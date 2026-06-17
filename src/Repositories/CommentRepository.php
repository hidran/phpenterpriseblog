<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Comment;
use PDO;

final readonly class CommentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<Comment>
     */
    public function allForPost(int $postId): array
    {
        $sql = 'SELECT * FROM post_comments WHERE post_id = :post_id ORDER BY datecreated DESC';
        $stm = $this->pdo->prepare($sql);
        $stm->execute(['post_id' => $postId]);
        return array_map(Comment::fromRow(...), $stm->fetchAll());
    }

    /**
     * @param array{post_id: int, user_id: int, comment: string, email: string} $data
     */
    public function save(array $data): int
    {
        $sql = 'INSERT INTO post_comments (post_id, user_id, comment, email, datecreated) '
             . 'VALUES (:post_id, :user_id, :comment, :email, NOW())';
        $stm = $this->pdo->prepare($sql);
        $stm->execute([
            'post_id' => $data['post_id'],
            'user_id' => $data['user_id'],
            'comment' => $data['comment'],
            'email'   => $data['email'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
