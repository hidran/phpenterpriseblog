<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\ConnectionFactory;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = ConnectionFactory::fromEnv();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['post_comments', 'posts', 'users', 'migrations'] as $t) {
            $this->pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        $sql = file_get_contents(dirname(__DIR__, 2) . '/database/migrations/0001_init.sql');
        if ($sql === false) {
            self::fail('Cannot read migration 0001_init.sql');
        }
        $this->pdo->exec($sql);
    }

    protected function seedUser(string $email = 'u@e.co'): int
    {
        $stm = $this->pdo->prepare('INSERT INTO users (username, email, password, roletype) VALUES (?,?,?,?)');
        $stm->execute(['user', $email, password_hash('secret123', PASSWORD_DEFAULT), 'user']);
        return (int) $this->pdo->lastInsertId();
    }
}
