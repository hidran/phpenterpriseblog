<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\View;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewTest extends TestCase
{
    private View $view;

    protected function setUp(): void
    {
        $this->view = new View(__DIR__ . '/../../Fixtures/views');
    }

    public function testRenderInterpolatesData(): void
    {
        self::assertSame('Hello, World!', trim($this->view->render('hello', ['name' => 'World'])));
    }

    public function testRenderEscapesHtml(): void
    {
        self::assertStringContainsString('&lt;script&gt;', $this->view->render('hello', ['name' => '<script>']));
    }

    public function testMissingViewThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->view->render('missing', []);
    }
}
