<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Env;
use App\Support\View;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Redis;
use Throwable;

final class HealthController extends BaseController
{
    public function __construct(View $view, private readonly PDO $pdo)
    {
        parent::__construct($view);
    }

    /**
     * @param array<string, string> $args
     */
    public function check(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $db = $this->probe(function (): bool {
            $stmt = $this->pdo->query('SELECT 1');
            return $stmt !== false && (int) $stmt->fetchColumn() === 1;
        });

        $redis = 'skipped';
        if (extension_loaded('redis')) {
            $redis = $this->probe(function (): bool {
                $r = new Redis();
                $r->connect(Env::string('REDIS_HOST', 'redis'), Env::int('REDIS_PORT', 6379), 1.0);
                $pw = Env::string('REDIS_PASSWORD', '');
                if ($pw !== '') {
                    $r->auth($pw);
                }
                return $r->ping() !== false;
            });
        }

        $status = ($db === 'ok' && $redis !== 'fail') ? 200 : 503;
        return $this->json([
            'db'      => $db,
            'redis'   => $redis,
            'version' => Env::string('APP_VERSION', 'dev'),
        ], $status);
    }

    /**
     * @param callable(): bool $fn
     */
    private function probe(callable $fn): string
    {
        try {
            return $fn() ? 'ok' : 'fail';
        } catch (Throwable) {
            return 'fail';
        }
    }
}
