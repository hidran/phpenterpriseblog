<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, string>                    $query
     * @param array<string, string|array<int,string>>  $post
     * @param array<string, string>                    $headers
     */
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $query,
        public readonly array $post,
        public readonly array $headers,
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with((string) $k, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr((string) $k, 5)));
                $headers[$name] = (string) $v;
            }
        }
        return new self(
            method:  (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            uri:     (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            query:   array_map(strval(...), $_GET),
            post:    $_POST,
            headers: $headers,
        );
    }

    public function postString(string $key, string $default = ''): string
    {
        $v = $this->post[$key] ?? $default;
        return is_array($v) ? $default : (string) $v;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
