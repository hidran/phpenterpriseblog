<?php

declare(strict_types=1);

namespace App\Database;

use App\Support\Env;
use PDO;

final class ConnectionFactory
{
    public static function fromEnv(): PDO
    {
        $driver   = Env::string('DB_DRIVER', 'mysql');
        $host     = Env::string('DB_HOST');
        $port     = Env::int('DB_PORT', 3306);
        $database = Env::string('DB_DATABASE');
        $user     = Env::string('DB_USERNAME');
        $password = Env::string('DB_PASSWORD');

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $driver,
            $host,
            $port,
            $database,
        );

        return new PdoConnection([
            'dsn'      => $dsn,
            'user'     => $user,
            'password' => $password,
        ])->pdo();
    }
}
