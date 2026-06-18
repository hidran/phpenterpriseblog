<?php

declare(strict_types=1);

namespace App\Cache;

use DateInterval;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class RedisCache implements CacheInterface
{
    private readonly Psr16Cache $inner;

    public function __construct(string $dsn, string $namespace = 'fb')
    {
        $client = RedisAdapter::createConnection($dsn);
        $adapter = new RedisAdapter($client, $namespace);
        $this->inner = new Psr16Cache($adapter);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->inner->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->inner->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->inner->delete($key);
    }

    public function clear(): bool
    {
        return $this->inner->clear();
    }

    /**
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->inner->getMultiple($keys, $default);
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return $this->inner->setMultiple($values, $ttl);
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return $this->inner->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->inner->has($key);
    }
}
