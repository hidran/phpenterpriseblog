<?php

declare(strict_types=1);

namespace Tests\Integration\Session;

use App\Session\RedisSessionHandler;
use App\Support\Env;
use PHPUnit\Framework\TestCase;
use Redis;

final class RedisSessionHandlerIntegrationTest extends TestCase
{
    public function testWriteReadRoundTrip(): void
    {
        $redis = new Redis();
        $redis->connect(Env::string('REDIS_HOST'), Env::int('REDIS_PORT', 6379));
        $pw = Env::string('REDIS_PASSWORD', '');
        if ($pw !== '') {
            $redis->auth($pw);
        }
        $h = new RedisSessionHandler($redis, ttl: 60, prefix: 'sess-test:');
        self::assertTrue($h->write('abc', 'payload'));
        self::assertSame('payload', $h->read('abc'));
        self::assertTrue($h->destroy('abc'));
        self::assertSame('', $h->read('abc'));
    }
}
