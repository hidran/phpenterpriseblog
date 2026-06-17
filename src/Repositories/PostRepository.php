<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;
use PDO;

class PostRepository
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    /**
     * @return list<Post>
     */
    public function all(): array
    {
        $sql = 'SELECT p.*, u.email FROM posts p '
             . 'INNER JOIN users u ON u.id = p.user_id '
             . 'ORDER BY p.datecreated DESC';
        $rows = $this->pdo->query($sql)?->fetchAll() ?: [];
        return array_map(Post::fromRow(...), $rows);
    }

    public function findById(int $id): ?Post
    {
        $sql = 'SELECT p.*, u.email FROM posts p '
             . 'INNER JOIN users u ON u.id = p.user_id '
             . 'WHERE p.id = :id';
        $stm = $this->pdo->prepare($sql);
        $stm->execute(['id' => $id]);
        $row = $stm->fetch();
        return $row === false ? null : Post::fromRow($row);
    }

    /**
     * @param array{user_id: int, title: string, message: string} $data
     */
    public function save(array $data): int
    {
        $sql = 'INSERT INTO posts (title, user_id, message, datecreated) '
             . 'VALUES (:title, :user_id, :message, NOW())';
        $stm = $this->pdo->prepare($sql);
        $stm->execute([
            'title'   => $data['title'],
            'user_id' => $data['user_id'],
            'message' => $data['message'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{title: string, message: string} $data
     */
    public function update(int $id, array $data): bool
    {
        $stm = $this->pdo->prepare('UPDATE posts SET title = :title, message = :message WHERE id = :id');
        $stm->execute([
            'title'   => $data['title'],
            'message' => $data['message'],
            'id'      => $id,
        ]);
        return $stm->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stm = $this->pdo->prepare('DELETE FROM posts WHERE id = :id');
        $stm->execute(['id' => $id]);
        return $stm->rowCount() > 0;
    }
}
