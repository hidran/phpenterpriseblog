<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class PdoConnection
{
    private readonly PDO $pdo;

    /**
     * @param array{dsn: string, user: string, password: string, options?: array<int, int>} $options
     */
    public function __construct(array $options)
    {
        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO(
            $options['dsn'],
            $options['user'],
            $options['password'],
            ($options['options'] ?? []) + $defaultOptions,
        );
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
