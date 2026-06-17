<?php

declare(strict_types=1);

namespace App\Http;

use League\Route\Router as LeagueRouter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Router implements RequestHandlerInterface
{
    private readonly LeagueRouter $router;

    /**
     * @param array<string, array<string, array{0: class-string, 1: string}>> $routes
     */
    public function __construct(array $routes, ContainerInterface $container)
    {
        $this->router = new LeagueRouter();
        $this->router->setStrategy(
            (new \League\Route\Strategy\ApplicationStrategy())
                ->setContainer($container)
        );

        foreach ($routes as $method => $table) {
            foreach ($table as $path => [$class, $action]) {
                $normalized = $path === '/' ? '/' : '/' . ltrim($path, '/');
                $normalized = preg_replace('/:([A-Za-z_][A-Za-z0-9_]*)/', '{$1}', $normalized);
                if ($normalized === null) {
                    throw new \RuntimeException("Route placeholder rewrite failed for path: {$path}");
                }
                $this->router->map(strtoupper($method), $normalized, [$class, $action]);
            }
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->handle($request);
    }
}
