<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private function user(string $email = 'a@b.co', string $rawPassword = 'secret123'): User
    {
        return new User(
            id: 1,
            username: 'demo',
            email: $email,
            password: password_hash($rawPassword, PASSWORD_DEFAULT),
            roletype: 'user',
        );
    }

    public function testLoginRejectsTokenMismatch(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $svc  = new AuthService($repo);
        $r    = $svc->verifyLogin('a@b.co', 'secret123', 'bad', 'good');
        self::assertFalse($r->success);
        self::assertSame('TOKEN MISMATCH', $r->message);
    }

    public function testLoginRejectsBadEmail(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $svc  = new AuthService($repo);
        self::assertSame('WRONG EMAIL', $svc->verifyLogin('nope', 'secret123', 't', 't')->message);
    }

    public function testLoginRejectsShortPassword(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $svc  = new AuthService($repo);
        self::assertSame('PASSWORD TOO SHORT', $svc->verifyLogin('a@b.co', '123', 't', 't')->message);
    }

    public function testLoginRejectsUnknownUser(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn(null);
        $svc = new AuthService($repo);
        self::assertSame('USER NOT FOUND', $svc->verifyLogin('a@b.co', 'secret123', 't', 't')->message);
    }

    public function testLoginRejectsBadPassword(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn($this->user(rawPassword: 'correct123'));
        $svc = new AuthService($repo);
        self::assertSame('WRONG PASSWORD', $svc->verifyLogin('a@b.co', 'wrong123', 't', 't')->message);
    }

    public function testLoginSucceedsWithCorrectPassword(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn($this->user(rawPassword: 'correct123'));
        $svc = new AuthService($repo);
        $r   = $svc->verifyLogin('a@b.co', 'correct123', 't', 't');
        self::assertTrue($r->success);
        self::assertNotNull($r->user);
    }

    public function testSignupRejectsExistingEmail(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn($this->user());
        $svc = new AuthService($repo);
        self::assertSame('USER ALREADY EXISTS', $svc->verifySignup('a@b.co', 'secret123', 't', 't')->message);
    }
}
