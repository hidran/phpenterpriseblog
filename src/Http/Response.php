<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }
}
