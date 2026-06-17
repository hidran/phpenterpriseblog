<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * @param array{username: string, email: string, password: string, roletype?: string} $data
     */
    public function save(array $data): int;
}
