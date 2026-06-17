<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    /**
     * @param array<string, array<string, array{0: class-string|string, 1: string}>> $routes
     */
    public function __construct(private readonly array $routes)
    {
    }

    /**
     * @return array{0: class-string|string, 1: string, 2: array<int, string>}
     */
    public function dispatch(string $method, string $uri): array
    {
        $path  = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $path  = trim($path, '/');
        $path  = $path === '' ? '/' : $path;
        $table = $this->routes[$method] ?? [];

        if (array_key_exists($path, $table)) {
            return [$table[$path][0], $table[$path][1], []];
        }

        foreach ($table as $pattern => $handler) {
            if (!str_contains($pattern, ':')) {
                continue;
            }
            $parts = preg_split('/(:[A-Za-z_][A-Za-z0-9_]*)/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
            $regexParts = array_map(
                static fn(string $p): string => str_starts_with($p, ':') ? '([A-Za-z0-9_\-]+)' : preg_quote($p, '@'),
                $parts,
            );
            $regex = '@^' . implode('', $regexParts) . '$@';
            if (preg_match($regex, $path, $matches) === 1) {
                array_shift($matches);
                return [$handler[0], $handler[1], $matches];
            }
        }

        throw new RouteNotFoundException("No route for {$method} /{$path}");
    }
}
