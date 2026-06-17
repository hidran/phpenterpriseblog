<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\PostRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class PostRepositorySaveRegressionTest extends TestCase
{
    public function testSavePassesMessageNotEmailToTheMessageColumn(): void
    {
        $stm = $this->createMock(PDOStatement::class);
        $stm->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params): bool {
                self::assertSame('actual message body', $params['message']);
                self::assertSame('actual title', $params['title']);
                self::assertSame(7, $params['user_id']);
                self::assertArrayNotHasKey('email', $params, 'PostRepository::save MUST NOT write email into message');
                return true;
            }));

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stm);
        $pdo->method('lastInsertId')->willReturn('99');

        $repo = new PostRepository($pdo);
        $id   = $repo->save([
            'user_id' => 7,
            'title'   => 'actual title',
            'message' => 'actual message body',
        ]);

        self::assertSame(99, $id);
    }
}
