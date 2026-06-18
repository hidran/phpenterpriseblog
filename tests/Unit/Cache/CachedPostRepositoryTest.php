<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Cache\CacheInterface;
use App\Cache\CachedPostRepository;
use App\Models\Post;
use DateInterval;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class CachedPostRepositoryTest extends TestCase
{
    private function fakeCache(): CacheInterface
    {
        return new class implements CacheInterface {
            /** @var array<string, mixed> */
            public array $store = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->store[$key] ?? $default;
            }

            public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
            {
                $this->store[$key] = $value;
                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->store[$key]);
                return true;
            }

            public function clear(): bool
            {
                $this->store = [];
                return true;
            }

            /**
             * @param iterable<string> $keys
             * @return iterable<string, mixed>
             */
            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                foreach ($keys as $k) {
                    yield $k => $this->get($k, $default);
                }
            }

            /**
             * @param iterable<string, mixed> $values
             */
            public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
            {
                foreach ($values as $k => $v) {
                    $this->set($k, $v, $ttl);
                }
                return true;
            }

            /**
             * @param iterable<string> $keys
             */
            public function deleteMultiple(iterable $keys): bool
            {
                foreach ($keys as $k) {
                    $this->delete($k);
                }
                return true;
            }

            public function has(string $key): bool
            {
                return isset($this->store[$key]);
            }
        };
    }

    private function post(int $id = 1): Post
    {
        return new Post(
            id: $id,
            title: 'title',
            message: 'message',
            userId: 2,
            datecreated: '2026-01-01 00:00:00',
            email: 'a@b.co',
        );
    }

    public function testSaveInvalidatesListKey(): void
    {
        $cache = $this->fakeCache();
        $cache->set('posts:list:v1', ['stale']);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);
        $pdo->method('lastInsertId')->willReturn('42');

        $repo = new CachedPostRepository($pdo, $cache);
        $id   = $repo->save(['user_id' => 1, 'title' => 't', 'message' => 'm']);

        self::assertSame(42, $id);
        self::assertNull($cache->get('posts:list:v1'));
    }

    public function testAllReturnsCachedListWithoutTouchingDb(): void
    {
        $cache = $this->fakeCache();
        $cache->set('posts:list:v1', [$this->post(1), $this->post(2)]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('query');

        $repo = new CachedPostRepository($pdo, $cache);
        $all  = $repo->all();

        self::assertCount(2, $all);
        self::assertSame(1, $all[0]->id);
    }

    public function testFindByIdReturnsCachedPostWithoutTouchingDb(): void
    {
        $cache = $this->fakeCache();
        $cache->set('posts:show:5:v1', $this->post(5));

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('prepare');

        $repo = new CachedPostRepository($pdo, $cache);
        $post = $repo->findById(5);

        self::assertNotNull($post);
        self::assertSame(5, $post->id);
    }

    public function testDeleteInvalidatesBothKeys(): void
    {
        $cache = $this->fakeCache();
        $cache->set('posts:list:v1', ['stale']);
        $cache->set('posts:show:7:v1', $this->post(7));

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('rowCount')->willReturn(1);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $repo = new CachedPostRepository($pdo, $cache);
        self::assertTrue($repo->delete(7));
        self::assertNull($cache->get('posts:list:v1'));
        self::assertNull($cache->get('posts:show:7:v1'));
    }
}
