<?php

declare(strict_types=1);

namespace App;

use App\Cache\CacheInterface;
use App\Cache\RedisCache;
use App\Controllers\HealthController;
use App\Database\ConnectionFactory;
use App\Http\Router;
use App\Session\RedisSessionHandler;
use App\Support\Env;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\View;
use Dotenv\Dotenv;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class Kernel
{
    public function __construct(private string $basePath)
    {
    }

    public function handle(): void
    {
        $this->loadEnv();
        $this->configureSession();
        session_start();

        $container = $this->buildContainer();
        $request   = $this->buildRequest();

        try {
            $response = $container->get(Router::class)->handle($request);
        } catch (NotFoundException) {
            $response = new Psr17Factory()->createResponse(404)
                ->withHeader('Content-Type', 'text/html; charset=utf-8');
            $response->getBody()->write('<h1>404 Not Found</h1>');
        } catch (MethodNotAllowedException $e) {
            $response = new Psr17Factory()->createResponse(405)
                ->withHeader('Content-Type', 'text/html; charset=utf-8')
                ->withHeader('Allow', implode(', ', $e->getAllowedMethods()));
            $response->getBody()->write('<h1>405 Method Not Allowed</h1>');
        }

        $this->emit($response);
    }

    private function loadEnv(): void
    {
        $envFile = $this->basePath . '/.env';
        if (is_file($envFile)) {
            Dotenv::createImmutable($this->basePath)->load();
        }
    }

    private function configureSession(): void
    {
        if (Env::string('SESSION_DRIVER', 'files') !== 'redis-tls') {
            return;
        }

        $redis = new \Redis();
        $redis->connect('tls://' . Env::string('REDIS_HOST'), Env::int('REDIS_PORT'));
        $pw = Env::string('REDIS_PASSWORD', '');
        if ($pw !== '') {
            $redis->auth($pw);
        }
        session_set_save_handler(new RedisSessionHandler($redis), true);
    }

    private function buildContainer(): Container
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer(cacheResolutions: true));

        $container->addShared(PDO::class, ConnectionFactory::fromEnv(...));
        $container->addShared(View::class, fn (): \App\Support\View => new View($this->basePath . '/resources/views'));
        $container->addShared(CacheInterface::class, fn (): CacheInterface => new RedisCache(Env::string('REDIS_DSN')));

        $container->add(PostRepository::class)->addArgument(PDO::class);
        $container->add(UserRepository::class)->addArgument(PDO::class);
        $container->add(CommentRepository::class)->addArgument(PDO::class);
        $container->add(AuthService::class)->addArgument(UserRepository::class);
        $container->add(HealthController::class)->addArguments([View::class, PDO::class]);

        $routes = require $this->basePath . '/config/routes.php';
        $container->addShared(Router::class, fn (): \App\Http\Router => new Router($routes, $container));

        return $container;
    }

    private function buildRequest(): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        return new ServerRequestCreator($factory, $factory, $factory, $factory)
            ->fromGlobals();
    }

    private function emit(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            $replace = strcasecmp((string) $name, 'Set-Cookie') !== 0;
            foreach ($values as $value) {
                header($name . ': ' . $value, $replace);
                $replace = false; // subsequent values for the same header name must append
            }
        }
        echo $response->getBody();
    }
}
