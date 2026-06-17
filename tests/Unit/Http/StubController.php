<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StubController
{
    /**
     * @param array<string, string> $args
     */
    public function hello(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        return new Response(200, [], 'hello ' . ($args['name'] ?? 'world'));
    }
}
