<?php

declare(strict_types=1);

namespace App\Cache;

use App\Models\Post;
use App\Repositories\PostRepository;
use PDO;

final class CachedPostRepository extends PostRepository
{
    private const TTL_LIST = 60;
    private const TTL_SHOW = 300;
    private const KEY_LIST = 'posts:list:v1';

    public function __construct(PDO $pdo, private readonly CacheInterface $cache)
    {
        parent::__construct($pdo);
    }

    /**
     * @return list<Post>
     */
    public function all(): array
    {
        $cached = $this->cache->get(self::KEY_LIST);
        if (is_array($cached)) {
            return array_values(array_filter($cached, static fn(mixed $p): bool => $p instanceof Post));
        }
        $fresh = parent::all();
        $this->cache->set(self::KEY_LIST, $fresh, self::TTL_LIST);
        return $fresh;
    }

    public function findById(int $id): ?Post
    {
        $key = self::keyShow($id);
        $cached = $this->cache->get($key);
        if ($cached instanceof Post) {
            return $cached;
        }
        $fresh = parent::findById($id);
        if ($fresh !== null) {
            $this->cache->set($key, $fresh, self::TTL_SHOW);
        }
        return $fresh;
    }

    /**
     * @param array{user_id: int, title: string, message: string} $data
     */
    public function save(array $data): int
    {
        $id = parent::save($data);
        $this->invalidate($id);
        return $id;
    }

    /**
     * @param array{title: string, message: string} $data
     */
    public function update(int $id, array $data): bool
    {
        $ok = parent::update($id, $data);
        $this->invalidate($id);
        return $ok;
    }

    public function delete(int $id): bool
    {
        $ok = parent::delete($id);
        $this->invalidate($id);
        return $ok;
    }

    private function invalidate(int $id): void
    {
        $this->cache->delete(self::KEY_LIST);
        $this->cache->delete(self::keyShow($id));
    }

    private static function keyShow(int $id): string
    {
        return "posts:show:{$id}:v1";
    }
}
