<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

final readonly class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $stm = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stm->execute(['email' => $email]);
        $row = $stm->fetch();
        return $row === false ? null : User::fromRow($row);
    }

    /**
     * @param array{username: string, email: string, password: string, roletype?: string} $data
     */
    public function save(array $data): int
    {
        $stm = $this->pdo->prepare(
            'INSERT INTO users (username, email, password, roletype) '
            . 'VALUES (:username, :email, :password, :roletype)'
        );
        $stm->execute([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'roletype' => $data['roletype'] ?? 'user',
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
