<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\PostRepository;
use Tests\Integration\IntegrationTestCase;

final class PostRepositoryIntegrationTest extends IntegrationTestCase
{
    public function testFullCrudRoundTrip(): void
    {
        $uid  = $this->seedUser();
        $repo = new PostRepository($this->pdo);

        $id = $repo->save(['user_id' => $uid, 'title' => 't1', 'message' => 'body1']);
        self::assertGreaterThan(0, $id);

        $found = $repo->findById($id);
        self::assertNotNull($found);
        self::assertSame('body1', $found->message);   // regression: not the email field

        $repo->update($id, ['title' => 't2', 'message' => 'body2']);
        self::assertSame('body2', $repo->findById($id)?->message);

        $all = $repo->all();
        self::assertCount(1, $all);

        $repo->delete($id);
        self::assertNull($repo->findById($id));
    }
}
