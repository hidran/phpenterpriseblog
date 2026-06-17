<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Router;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\Http\Exception\NotFoundException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class RouterTest extends TestCase
{
    private function router(): Router
    {
        $c = new Container();
        $c->delegate(new ReflectionContainer(cacheResolutions: true));
        return new Router([
            'GET' => ['/' => [StubController::class, 'hello'], 'hello/:name' => [StubController::class, 'hello']],
        ], $c);
    }

    private function request(string $method, string $uri): ServerRequestInterface
    {
        return new Psr17Factory()->createServerRequest($method, $uri);
    }

    public function testExactRouteReturnsHandlerResponse(): void
    {
        $response = $this->router()->handle($this->request('GET', '/'));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('hello world', (string) $response->getBody());
    }

    public function testParameterizedRouteExtractsArg(): void
    {
        $response = $this->router()->handle($this->request('GET', '/hello/anna'));
        self::assertSame('hello anna', (string) $response->getBody());
    }

    public function testUnknownRouteThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->router()->handle($this->request('GET', '/nope'));
    }
}
