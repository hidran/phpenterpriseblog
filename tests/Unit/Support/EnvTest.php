<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Env;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnvTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['TEST_VAR']);
    }

    public function testStringReadsFromEnvSuper(): void
    {
        $_ENV['TEST_VAR'] = 'hello';
        self::assertSame('hello', Env::string('TEST_VAR'));
    }

    public function testStringFallsBackToDefault(): void
    {
        self::assertSame('default', Env::string('MISSING_VAR', 'default'));
    }

    public function testStringThrowsOnMissingWithoutDefault(): void
    {
        $this->expectException(RuntimeException::class);
        Env::string('TOTALLY_MISSING_VAR');
    }

    public function testIntCoercion(): void
    {
        $_ENV['TEST_VAR'] = '42';
        self::assertSame(42, Env::int('TEST_VAR'));
    }

    public function testIntThrowsOnNonInt(): void
    {
        $_ENV['TEST_VAR'] = 'nope';
        $this->expectException(RuntimeException::class);
        Env::int('TEST_VAR');
    }

    public function testBoolTrueValues(): void
    {
        foreach (['1', 'true', 'yes', 'on'] as $v) {
            $_ENV['TEST_VAR'] = $v;
            self::assertTrue(Env::bool('TEST_VAR'));
        }
    }

    public function testBoolDefaultUsedWhenMissing(): void
    {
        self::assertTrue(Env::bool('STILL_MISSING_VAR', true));
        self::assertFalse(Env::bool('STILL_MISSING_VAR', false));
    }
}
