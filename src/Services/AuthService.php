<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

final readonly class AuthService
{
    public function __construct(private UserRepository $users)
    {
    }

    public function verifyLogin(string $email, string $password, string $token, string $sessionToken): AuthResult
    {
        if (!hash_equals($sessionToken, $token)) {
            return AuthResult::failure('TOKEN MISMATCH');
        }
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            return AuthResult::failure('WRONG EMAIL');
        }
        if (strlen($password) < 6) {
            return AuthResult::failure('PASSWORD TOO SHORT');
        }
        $user = $this->users->findByEmail($email);
        if (!$user instanceof \App\Models\User) {
            return AuthResult::failure('USER NOT FOUND');
        }
        if (!password_verify($password, $user->password)) {
            return AuthResult::failure('WRONG PASSWORD');
        }
        return AuthResult::success('LOGGED IN', $user);
    }

    public function verifySignup(string $email, string $password, string $token, string $sessionToken): AuthResult
    {
        if (!hash_equals($sessionToken, $token)) {
            return AuthResult::failure('TOKEN MISMATCH');
        }
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            return AuthResult::failure('WRONG EMAIL');
        }
        if (strlen($password) < 6) {
            return AuthResult::failure('PASSWORD TOO SHORT');
        }
        if ($this->users->findByEmail($email) instanceof \App\Models\User) {
            return AuthResult::failure('USER ALREADY EXISTS');
        }
        return AuthResult::success('SIGNUP OK');
    }

    public function createUser(string $username, string $email, string $password): User
    {
        $id = $this->users->save([
            'username' => $username,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return new User(
            id: $id,
            username: $username,
            email: $email,
            password: '',
            roletype: 'user',
        );
    }
}
