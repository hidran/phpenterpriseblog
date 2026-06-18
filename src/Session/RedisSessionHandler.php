<?php

declare(strict_types=1);

namespace App\Session;

use Redis;
use SessionHandlerInterface;

final class RedisSessionHandler implements SessionHandlerInterface
{
    public function __construct(
        private readonly Redis $redis,
        private readonly int $ttl = 7200,
        private readonly string $prefix = 'sess:',
    ) {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $v = $this->redis->get($this->prefix . $id);
        return is_string($v) ? $v : '';
    }

    public function write(string $id, string $data): bool
    {
        return $this->redis->setex($this->prefix . $id, $this->ttl, $data) === true;
    }

    public function destroy(string $id): bool
    {
        $this->redis->del($this->prefix . $id);
        return true;
    }

    public function gc(int $maxLifetime): int
    {
        return 0; // Redis TTL handles expiry
    }
}
