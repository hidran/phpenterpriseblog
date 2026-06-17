<?php

declare(strict_types=1);

namespace App;

use App\Controllers\BaseController;
use App\Database\ConnectionFactory;
use App\Http\Request;
use App\Http\Response;
use App\Http\RouteNotFoundException;
use App\Http\Router;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\View;
use Dotenv\Dotenv;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PDO;
use Throwable;

final class Kernel
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function handle(): void
    {
        $this->loadEnv();
        session_start();

        $container = $this->buildContainer();
        $request   = $container->get(Request::class);

        $routes = require $this->basePath . '/config/routes.php';
        $router = new Router($routes);

        try {
            [$class, $method, $params] = $router->dispatch($request->method, $request->uri);
        } catch (RouteNotFoundException $e) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        /** @var object $controller */
        $controller = $container->get($class);
        $controller->$method(...$params);

        if ($controller instanceof BaseController) {
            $controller->display();
        }
    }

    private function loadEnv(): void
    {
        $envFile = $this->basePath . '/.env';
        if (is_file($envFile)) {
            Dotenv::createImmutable($this->basePath)->load();
        }
    }

    private function buildContainer(): Container
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer(cacheResolutions: true));

        $container->addShared(PDO::class, ConnectionFactory::fromEnv(...));
        $container->addShared(Request::class, Request::fromGlobals(...));
        $container->addShared(View::class, fn() => new View($this->basePath . '/resources/views'));

        $container->add(PostRepository::class)->addArgument(PDO::class);
        $container->add(UserRepository::class)->addArgument(PDO::class);
        $container->add(CommentRepository::class)->addArgument(PDO::class);
        $container->add(AuthService::class)->addArgument(UserRepository::class);

        return $container;
    }
}
