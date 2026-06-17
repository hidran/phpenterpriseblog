<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\RouteNotFoundException;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function routes(): array
    {
        return [
            'GET'  => [
                '/'             => ['Ctrl', 'home'],
                'posts'         => ['Ctrl', 'index'],
                'posts/:id'     => ['Ctrl', 'show'],
                'posts/:id/edit' => ['Ctrl', 'edit'],
            ],
            'POST' => [
                'posts/:id/comments' => ['Ctrl', 'comment'],
            ],
        ];
    }

    public function testExactMatchReturnsHandler(): void
    {
        $r = new Router($this->routes());
        $this->assertSame(['Ctrl', 'home', []], $r->dispatch('GET', '/'));
        $this->assertSame(['Ctrl', 'index', []], $r->dispatch('GET', '/posts'));
    }

    public function testParameterizedMatchExtractsParams(): void
    {
        $r = new Router($this->routes());
        $this->assertSame(['Ctrl', 'show', ['42']], $r->dispatch('GET', '/posts/42'));
        $this->assertSame(['Ctrl', 'edit', ['7']], $r->dispatch('GET', '/posts/7/edit'));
    }

    public function testMethodScopedRoutes(): void
    {
        $r = new Router($this->routes());
        $this->assertSame(['Ctrl', 'comment', ['9']], $r->dispatch('POST', '/posts/9/comments'));
    }

    public function testUnknownRouteThrows(): void
    {
        $this->expectException(RouteNotFoundException::class);
        (new Router($this->routes()))->dispatch('GET', '/nope');
    }
}
