<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Env
{
    public static function string(string $key, ?string $default = null): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (in_array($value, [false, null, ''], true)) {
            if ($default === null) {
                throw new RuntimeException("Missing required env var: {$key}");
            }
            return $default;
        }
        return (string) $value;
    }

    public static function int(string $key, ?int $default = null): int
    {
        $raw = self::string($key, $default === null ? null : (string) $default);
        if (!preg_match('/^-?\d+$/', $raw)) {
            throw new RuntimeException("Env var {$key} is not an integer: {$raw}");
        }
        return (int) $raw;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $raw = strtolower(self::string($key, $default ? 'true' : 'false'));
        return in_array($raw, ['1', 'true', 'yes', 'on'], true);
    }
}
