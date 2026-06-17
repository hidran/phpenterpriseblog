<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\View;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
    public function __construct(protected readonly View $view)
    {
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function respond(string $body, int $status = 200, array $headers = []): ResponseInterface
    {
        $html = $this->view->render('layouts/default', ['content' => $body]);
        $headers += ['Content-Type' => 'text/html; charset=utf-8'];
        return new Response($status, $headers, $html);
    }

    protected function redirect(string $url, int $status = 302): ResponseInterface
    {
        return new Response($status, ['Location' => $url]);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $status = 200): ResponseInterface
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR));
    }
}
