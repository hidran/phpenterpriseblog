<?php

declare(strict_types=1);

namespace Tests\Integration\Cache;

use App\Cache\CachedPostRepository;
use App\Cache\RedisCache;
use App\Support\Env;
use Tests\Integration\IntegrationTestCase;

final class CachedPostRepositoryIntegrationTest extends IntegrationTestCase
{
    public function testHitMissAndInvalidate(): void
    {
        $uid   = $this->seedUser();
        $cache = new RedisCache(Env::string('REDIS_DSN'), 'fb-test-' . bin2hex(random_bytes(4)));
        $cache->clear();

        $repo = new CachedPostRepository($this->pdo, $cache);
        $id   = $repo->save(['user_id' => $uid, 'title' => 't', 'message' => 'm']);

        $afterSave = $cache->get('posts.list.v1');
        self::assertNull($afterSave, 'save() invalidates list');

        $first  = $repo->all();
        $cached = $cache->get('posts.list.v1');
        self::assertNotNull($cached, 'all() populates cache');
        self::assertEquals($first, $cached);

        $repo->update($id, ['title' => 'new', 'message' => 'm2']);
        $afterUpdate = $cache->get('posts.list.v1');
        self::assertNull($afterUpdate, 'update() invalidates list');
    }
}
