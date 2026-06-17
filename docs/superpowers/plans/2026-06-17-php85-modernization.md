# PHP 8.5 Modernization Implementation Plan — `phpenterpriseblog`

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `phpenterpriseblog` — a PHP 8.5, enterprise-shaped remake of the `freeblog` Udemy MVC blog — with Composer/PSR-4, src-layout, Models+Repositories+Services, Redis cache & sessions, Docker, PHPStan/PHPCS/PHPUnit/Playwright CI, and EKS deploy via Helm + GitHub Actions OIDC.

**Architecture:** Single `src/` namespace root with `App\`. Thin Controllers + Services + Repositories pattern. Kernel-driven boot via PSR-11 container. Production Docker image (PHP 8.5-fpm-alpine + nginx). Two-environment EKS promotion (staging → manual approval → prod) with atomic Helm releases.

**Tech Stack:** PHP 8.4/8.5, Composer (PSR-4), vlucas/phpdotenv, league/container, symfony/cache (Redis adapter), monolog, PHPUnit 11, PHPStan 2, PHP_CodeSniffer 3 + Slevomat, Rector 2, Playwright (TS), Docker, nginx, MySQL 8.4, Redis 7, Helm 3, EKS ≥ 1.30, GitHub Actions.

**Source reference:** `/Users/hidranarias/projects/udemy/freeblog` is the original Udemy project. Workers may read it for behavioral reference but every file ends up newly created under `/Users/hidranarias/projects/udemy/phpenterpriseblog/`.

**Working directory for ALL tasks:** `/Users/hidranarias/projects/udemy/phpenterpriseblog/`.

## Global Constraints

- **PHP floor:** `php: ^8.4` in `composer.json`. CI matrix runs 8.4 AND 8.5; both must pass.
- **Namespace root:** PSR-4 — `App\` → `src/`. Helpers via Composer `files` autoload.
- **No long-lived AWS keys.** All AWS auth from CI uses GitHub OIDC.
- **No `freeblog` references** in the new project except as historical/reference notes. Names: ECR repo `phpenterpriseblog`, IAM roles `phpenterpriseblog-{ci-ecr,cd-eks}`, K8s namespaces `phpenterpriseblog-{staging,prod}`, Helm release `phpenterpriseblog`, Secrets Manager prefix `phpenterpriseblog/{env}/`.
- **Schema renames are one-shot in migration `0001`:** `postscomments` → `post_comments`; charset `utf8mb4`; all column types preserved otherwise.
- **Behavior preservation:** the legacy URL surface (`/`, `/posts`, `/posts/{id}`, `/posts/create`, `/posts/{id}/edit`, `/posts/{id}/comments`, `/auth/login`, `/auth/signup`, `/auth/logout`) must continue to work end-to-end.
- **PHPStan starts at level 6.** Ratchets to level 8 only in Phase 8. Baseline must not grow within a PR.
- **PHPCS = PSR-12 + SlevomatCodingStandard.TypeHints.{Parameter,Return,Property}TypeHint.** Excludes `vendor/`, `database/`, `resources/views/`.
- **TDD where it pays off** (Router, AuthService, Repositories, Cache decorator). Configuration-shaped tasks (Dockerfile, Helm templates, CI YAML) use deterministic verification commands (`docker build`, `helm template`, `act` / dry-run) instead of unit tests.
- **Commits:** each task ends with one commit. Commit message format: `<area>: <short imperative>` (e.g., `router: add parameterized route matching`). All commits trailer: `Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>`.
- **Migrations are forward-only.** No `DROP` columns in the same release that introduces their replacement.

## Course companion — per-task discipline

This project is also a Udemy course. Sibling repository `../phpblog-udemy/` holds one lesson per task. **Every task in Phases 1–8 — without exception, even if the task body doesn't repeat it — applies the following discipline immediately after its final commit:**

### Step A — Tag the commit in `phpenterpriseblog`

For Task `<phase>.<task>`, tag the just-made commit with the course tag:
```bash
git tag lesson-<phase>-<task>          # examples: lesson-1-1, lesson-3-3, lesson-7-5
```
This is what lets a student run `git checkout lesson-1-3` and see exactly the state at the end of that lesson.

### Step B — Write the companion lesson in `phpblog-udemy`

Create the matching lesson file in the sibling repo:
- Path: `../phpblog-udemy/<MM>-<module-slug>/<MM>-<NN>-<lesson-slug>.md`
- Module slug ↔ phase mapping is in `../phpblog-udemy/COURSE_OUTLINE.md`. Example: Task 1.3 → `../phpblog-udemy/01-foundation/01-03-router-with-tdd.md`.
- Use `../phpblog-udemy/templates/lesson.md` as the structure — every section in that template must be filled in (no placeholders).
- Required sections:
  - **YAML frontmatter** with correct `module`, `lesson`, `duration_minutes` (estimate), `git_tag`, `related_code` (files touched), `prereq_lessons`.
  - **The "why" — teacher's notes:** *the most important section.* Explain the choice being made, what alternatives were considered, what trade-off is being accepted, what real-world failure mode this guards against. Two to four paragraphs minimum.
  - **Principles in play:** name the engineering principles demonstrated (e.g. *Single Responsibility*, *TDD*, *Open/Closed*, *Composition over Inheritance*) and for each say *why it applies here*.
  - **Walkthrough:** a per-section recording script with rough time estimates summing to roughly the `duration_minutes`.
  - **Try it yourself / Common pitfalls / Recap / Next lesson:** all filled in.

### Step C — Commit the lesson in `phpblog-udemy`

```bash
cd ../phpblog-udemy
git add <MM>-<module-slug>/<MM>-<NN>-<lesson-slug>.md
git commit -m "lesson <MM>-<NN>: <short topic>

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
cd -
```

### Rules of thumb

- **Code commit first, then tag, then write lesson, then commit lesson.** The lesson references concrete file contents from the just-made code commit.
- **Lessons must match the code actually written.** If a subagent deviates from the planned snippet, the lesson reflects what was built, not what the plan said.
- **No skipping.** Every task in Phases 1–8 ships a lesson. Phase 0 (Task 0.1) skips — Module 00 lessons are hand-written.
- **Length:** 800–1500 words. The "why" section carries the weight.
- **Filename slugs:** lower-kebab-case, descriptive (`01-03-router-with-tdd.md`, not `01-03-task.md`).

A final task (8.6) builds the PDFs from all lessons via `make -C build all` in `phpblog-udemy/`.

---

## Phase 0 — Workspace prerequisites

### Task 0.1: Verify environment

**Files:** none (read-only checks).

**Interfaces:**
- Consumes: nothing
- Produces: confidence that local prerequisites exist

- [ ] **Step 1: Verify PHP 8.4 or 8.5 available**

Run: `php -v`
Expected: `PHP 8.4.x` or `PHP 8.5.x`. If neither: install via `brew install php@8.5` or use Docker for all PHP commands going forward.

- [ ] **Step 2: Verify Composer installed**

Run: `composer --version`
Expected: `Composer version 2.x.x`.

- [ ] **Step 3: Verify Docker running**

Run: `docker info | head -3`
Expected: no error, shows Server Version.

- [ ] **Step 4: Verify Node 20+ for Playwright (Phase 8)**

Run: `node -v`
Expected: `v20.x` or higher.

- [ ] **Step 5: Verify in correct repo**

Run: `pwd && git log --oneline -1`
Expected: `/Users/hidranarias/projects/udemy/phpenterpriseblog` and the root-commit containing the design spec.

No commit for Task 0.1 — read-only.

---

## Phase 1 — Foundation

### Task 1.1: Composer init + runtime + dev dependencies

**Files:**
- Create: `composer.json`
- Create: `composer.lock` (generated)

**Interfaces:**
- Consumes: nothing
- Produces: PSR-4 autoload (`App\` → `src/`), vendor/, composer scripts (`cs`, `stan`, `test`, `test:int`, `test:all`, `rector`, `ci`)

- [ ] **Step 1: Write `composer.json`**

```json
{
  "name": "hidran/phpenterpriseblog",
  "description": "PHP 8.5 enterprise-shaped remake of the freeblog Udemy MVC project.",
  "type": "project",
  "license": "MIT",
  "require": {
    "php": "^8.4",
    "ext-pdo": "*",
    "ext-pdo_mysql": "*",
    "ext-mbstring": "*",
    "ext-json": "*",
    "ext-intl": "*",
    "vlucas/phpdotenv": "^5.6",
    "psr/simple-cache": "^3.0",
    "symfony/cache": "^7.2",
    "psr/log": "^3.0",
    "monolog/monolog": "^3.8",
    "psr/container": "^2.0",
    "league/container": "^4.2"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.5",
    "phpstan/phpstan": "^2.1",
    "phpstan/extension-installer": "^1.4",
    "phpstan/phpstan-phpunit": "^2.0",
    "squizlabs/php_codesniffer": "^3.11",
    "slevomat/coding-standard": "^8.15",
    "friendsofphp/php-cs-fixer": "^3.68",
    "rector/rector": "^2.0"
  },
  "autoload": {
    "psr-4": { "App\\": "src/" },
    "files": [ "src/Support/helpers.php" ]
  },
  "autoload-dev": {
    "psr-4": { "Tests\\": "tests/" }
  },
  "config": {
    "sort-packages": true,
    "allow-plugins": {
      "phpstan/extension-installer": true,
      "dealerdirect/phpcodesniffer-composer-installer": true
    }
  },
  "scripts": {
    "cs":         "phpcs",
    "cs:fix":     "php-cs-fixer fix",
    "stan":       "phpstan analyse --memory-limit=512M",
    "test":       "phpunit --testsuite=unit",
    "test:int":   "phpunit --testsuite=integration",
    "test:all":   "phpunit",
    "rector":     "rector process --dry-run",
    "rector:fix": "rector process",
    "ci":         [ "@cs", "@stan", "@test:all" ]
  }
}
```

- [ ] **Step 2: Create the helpers file the autoload references**

Create `src/Support/helpers.php`:
```php
<?php

declare(strict_types=1);
// Global helpers — kept thin. Most logic lives in App\Support typed classes.
```

- [ ] **Step 3: Install**

Run: `composer install`
Expected: `Generating optimized autoload files`. `vendor/` created. No errors.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock src/Support/helpers.php
git commit -m "composer: initialize PSR-4 project with runtime + dev deps

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.2: `.env` infrastructure + typed `Env` accessor

**Files:**
- Create: `.env.example`
- Create: `.env` (gitignored; copy of example)
- Create: `src/Support/Env.php`
- Test: `tests/Unit/Support/EnvTest.php` (deferred to Phase 3 — Env logic is covered by repository tests in Phase 1)

**Interfaces:**
- Consumes: nothing
- Produces: `App\Support\Env::string(string $key, ?string $default = null): string`, `Env::int(string $key, ?int $default = null): int`, `Env::bool(string $key, bool $default = false): bool`. All throw `RuntimeException` if the key is missing and no default is supplied.

- [ ] **Step 1: Write `.env.example`**

```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_KEY=change-me-32-bytes-base64

DB_DRIVER=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=phpenterpriseblog
DB_USERNAME=root
DB_PASSWORD=root

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=devpass
REDIS_DSN=redis://:devpass@redis:6379/0

CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME_MINUTES=120

LOG_CHANNEL=stderr
LOG_LEVEL=info
```

- [ ] **Step 2: Copy to `.env`**

Run: `cp .env.example .env`

- [ ] **Step 3: Write `src/Support/Env.php`**

```php
<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Env
{
    public static function string(string $key, ?string $default = null): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
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
```

- [ ] **Step 4: Smoke-check via REPL**

Run: `php -r "require 'vendor/autoload.php'; \$d = Dotenv\\Dotenv::createImmutable(__DIR__); \$d->load(); echo App\\Support\\Env::string('APP_ENV'), PHP_EOL;"`
Expected: `local`.

- [ ] **Step 5: Commit**

```bash
git add .env.example src/Support/Env.php
git commit -m "env: add .env infra and typed Env accessor

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.3: Port the Router with PHP 8.5 hardening

**Files:**
- Create: `src/Http/Router.php`
- Reference (read-only): `/Users/hidranarias/projects/udemy/freeblog/core/Router.php`

**Interfaces:**
- Consumes: nothing
- Produces:
  - `App\Http\Router::__construct(array<string, array<string, array{0: class-string, 1: string}>> $routes)`
  - `Router::dispatch(string $method, string $uri): array{0: class-string, 1: string, 2: array<int, string>}`
  - Throws `App\Http\RouteNotFoundException` on no match.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Http/RouterTest.php`:
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\RouteNotFoundException;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function routes(): array
    {
        return [
            'GET'  => [
                '/'             => ['Ctrl', 'home'],
                'posts'         => ['Ctrl', 'index'],
                'posts/:id'     => ['Ctrl', 'show'],
                'posts/:id/edit' => ['Ctrl', 'edit'],
            ],
            'POST' => [
                'posts/:id/comments' => ['Ctrl', 'comment'],
            ],
        ];
    }

    public function testExactMatchReturnsHandler(): void
    {
        $r = new Router($this->routes());
        $this->assertSame(['Ctrl', 'home', []], $r->dispatch('GET', '/'));
        $this->assertSame(['Ctrl', 'index', []], $r->dispatch('GET', '/posts'));
    }

    public function testParameterizedMatchExtractsParams(): void
    {
        $r = new Router($this->routes());
        $this->assertSame(['Ctrl', 'show', ['42']], $r->dispatch('GET', '/posts/42'));
        $this->assertSame(['Ctrl', 'edit', ['7']], $r->dispatch('GET', '/posts/7/edit'));
    }

    public function testMethodScopedRoutes(): void
    {
        $r = new Router($this->routes());
        $this->assertSame(['Ctrl', 'comment', ['9']], $r->dispatch('POST', '/posts/9/comments'));
    }

    public function testUnknownRouteThrows(): void
    {
        $this->expectException(RouteNotFoundException::class);
        (new Router($this->routes()))->dispatch('GET', '/nope');
    }
}
```

(The PHPUnit config to run this comes in Phase 3. We will run the test directly with `vendor/bin/phpunit --no-configuration` for now.)

- [ ] **Step 2: Run test to confirm it fails**

Run: `vendor/bin/phpunit --no-configuration tests/Unit/Http/RouterTest.php`
Expected: error — `Class "App\Http\Router" not found`.

- [ ] **Step 3: Write `src/Http/RouteNotFoundException.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class RouteNotFoundException extends RuntimeException
{
}
```

- [ ] **Step 4: Write `src/Http/Router.php`**

```php
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
            $regex = '@^' . preg_replace('/:[A-Za-z_][A-Za-z0-9_]*/', '([A-Za-z0-9_\-]+)', preg_quote($pattern, '@')) . '$@';
            if (preg_match($regex, $path, $matches) === 1) {
                array_shift($matches);
                return [$handler[0], $handler[1], $matches];
            }
        }

        throw new RouteNotFoundException("No route for {$method} /{$path}");
    }
}
```

- [ ] **Step 5: Run test to confirm it passes**

Run: `vendor/bin/phpunit --no-configuration tests/Unit/Http/RouterTest.php`
Expected: `OK (4 tests)`.

- [ ] **Step 6: Commit**

```bash
git add src/Http/Router.php src/Http/RouteNotFoundException.php tests/Unit/Http/RouterTest.php
git commit -m "router: port routing with PSR-4, strict types, and unit tests

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.4: Database connection — `ConnectionFactory` + `PdoConnection`

**Files:**
- Create: `src/Database/PdoConnection.php`
- Create: `src/Database/ConnectionFactory.php`

**Interfaces:**
- Consumes: `App\Support\Env`
- Produces:
  - `App\Database\ConnectionFactory::fromEnv(): PDO`
  - `App\Database\PdoConnection::__construct(array{dsn:string,user:string,password:string,options?:array<int,int>})`
  - `PdoConnection::pdo(): PDO`

- [ ] **Step 1: Write `src/Database/PdoConnection.php`**

```php
<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class PdoConnection
{
    private readonly PDO $pdo;

    /**
     * @param array{dsn: string, user: string, password: string, options?: array<int, int>} $options
     */
    public function __construct(array $options)
    {
        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO(
            $options['dsn'],
            $options['user'],
            $options['password'],
            ($options['options'] ?? []) + $defaultOptions,
        );
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
```

- [ ] **Step 2: Write `src/Database/ConnectionFactory.php`**

```php
<?php

declare(strict_types=1);

namespace App\Database;

use App\Support\Env;
use PDO;

final class ConnectionFactory
{
    public static function fromEnv(): PDO
    {
        $driver   = Env::string('DB_DRIVER', 'mysql');
        $host     = Env::string('DB_HOST');
        $port     = Env::int('DB_PORT', 3306);
        $database = Env::string('DB_DATABASE');
        $user     = Env::string('DB_USERNAME');
        $password = Env::string('DB_PASSWORD');

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $driver,
            $host,
            $port,
            $database,
        );

        return (new PdoConnection([
            'dsn'      => $dsn,
            'user'     => $user,
            'password' => $password,
        ]))->pdo();
    }
}
```

- [ ] **Step 3: Sanity-check (no DB required)**

Run: `php -r "require 'vendor/autoload.php'; echo class_exists(App\\Database\\ConnectionFactory::class) ? 'ok' : 'no';"`
Expected: `ok`.

- [ ] **Step 4: Commit**

```bash
git add src/Database/
git commit -m "db: add ConnectionFactory + PdoConnection (utf8mb4, exceptions, assoc fetch)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.5: Post entity + PostRepository (SQL extracted from old Model)

**Files:**
- Create: `src/Models/Post.php`
- Create: `src/Repositories/PostRepository.php`
- Reference: `/Users/hidranarias/projects/udemy/freeblog/app/models/Post.php`

**Interfaces:**
- Consumes: `PDO`
- Produces:
  - `App\Models\Post` immutable DTO with public readonly typed properties (`id`, `title`, `message`, `userId`, `datecreated`, `email`).
  - `App\Repositories\PostRepository::__construct(PDO $pdo)`
  - `all(): list<Post>` — joined with `users` by `user_id`, ordered by `datecreated DESC`.
  - `findById(int $id): ?Post`
  - `save(array{user_id:int, title:string, message:string} $data): int` — returns new post id.
  - `update(int $id, array{title:string, message:string} $data): bool`
  - `delete(int $id): bool`

- [ ] **Step 1: Write `src/Models/Post.php`**

```php
<?php

declare(strict_types=1);

namespace App\Models;

final class Post
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $message,
        public readonly int $userId,
        public readonly string $datecreated,
        public readonly string $email,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:          (int) $row['id'],
            title:       (string) $row['title'],
            message:     (string) $row['message'],
            userId:      (int) $row['user_id'],
            datecreated: (string) $row['datecreated'],
            email:       (string) $row['email'],
        );
    }
}
```

- [ ] **Step 2: Write `src/Repositories/PostRepository.php`**

> **Bug-fix preserved here:** the legacy `Post::save` wrote `email` into the `message` column. The new repository writes `message` correctly — and the regression test goes in Phase 3.

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;
use PDO;

class PostRepository
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    /**
     * @return list<Post>
     */
    public function all(): array
    {
        $sql = 'SELECT p.*, u.email FROM posts p '
             . 'INNER JOIN users u ON u.id = p.user_id '
             . 'ORDER BY p.datecreated DESC';
        $rows = $this->pdo->query($sql)?->fetchAll() ?: [];
        return array_map(Post::fromRow(...), $rows);
    }

    public function findById(int $id): ?Post
    {
        $sql = 'SELECT p.*, u.email FROM posts p '
             . 'INNER JOIN users u ON u.id = p.user_id '
             . 'WHERE p.id = :id';
        $stm = $this->pdo->prepare($sql);
        $stm->execute(['id' => $id]);
        $row = $stm->fetch();
        return $row === false ? null : Post::fromRow($row);
    }

    /**
     * @param array{user_id: int, title: string, message: string} $data
     */
    public function save(array $data): int
    {
        $sql = 'INSERT INTO posts (title, user_id, message, datecreated) '
             . 'VALUES (:title, :user_id, :message, NOW())';
        $stm = $this->pdo->prepare($sql);
        $stm->execute([
            'title'   => $data['title'],
            'user_id' => $data['user_id'],
            'message' => $data['message'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{title: string, message: string} $data
     */
    public function update(int $id, array $data): bool
    {
        $stm = $this->pdo->prepare('UPDATE posts SET title = :title, message = :message WHERE id = :id');
        $stm->execute([
            'title'   => $data['title'],
            'message' => $data['message'],
            'id'      => $id,
        ]);
        return $stm->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stm = $this->pdo->prepare('DELETE FROM posts WHERE id = :id');
        $stm->execute(['id' => $id]);
        return $stm->rowCount() > 0;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Models/Post.php src/Repositories/PostRepository.php
git commit -m "posts: split Post DTO from PostRepository SQL (fix message-vs-email bug)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.6: User entity + UserRepository (delete mysqli dead code)

**Files:**
- Create: `src/Models/User.php`
- Create: `src/Repositories/UserRepository.php`
- Reference: `/Users/hidranarias/projects/udemy/freeblog/app/models/User.php` — note `delete()` and `getUser()` use `$GLOBALS['mysqli']` and **must NOT be ported**.

**Interfaces:**
- Consumes: `PDO`
- Produces:
  - `App\Models\User` readonly DTO (`id`, `username`, `email`, `password`, `roletype`).
  - `App\Repositories\UserRepository::findByEmail(string $email): ?User`
  - `UserRepository::save(array{username:string, email:string, password:string, roletype?:string} $data): int` — returns insert id; throws `PDOException` on duplicate email.

- [ ] **Step 1: Write `src/Models/User.php`**

```php
<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly string $roletype,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:       (int) $row['id'],
            username: (string) $row['username'],
            email:    (string) $row['email'],
            password: (string) $row['password'],
            roletype: (string) ($row['roletype'] ?? 'user'),
        );
    }
}
```

- [ ] **Step 2: Write `src/Repositories/UserRepository.php`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $stm = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stm->execute(['email' => $email]);
        $row = $stm->fetch();
        return $row === false ? null : User::fromRow($row);
    }

    /**
     * @param array{username: string, email: string, password: string, roletype?: string} $data
     */
    public function save(array $data): int
    {
        $stm = $this->pdo->prepare(
            'INSERT INTO users (username, email, password, roletype) '
            . 'VALUES (:username, :email, :password, :roletype)'
        );
        $stm->execute([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'roletype' => $data['roletype'] ?? 'user',
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Models/User.php src/Repositories/UserRepository.php
git commit -m "users: add User DTO + UserRepository (mysqli dead code dropped)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.7: Comment entity + CommentRepository

**Files:**
- Create: `src/Models/Comment.php`
- Create: `src/Repositories/CommentRepository.php`

**Interfaces:**
- Consumes: `PDO`
- Produces:
  - `App\Models\Comment` readonly DTO (`id`, `postId`, `userId`, `comment`, `email`, `datecreated`).
  - `App\Repositories\CommentRepository::allForPost(int $postId): list<Comment>`
  - `CommentRepository::save(array{post_id:int, user_id:int, comment:string, email:string} $data): int`

- [ ] **Step 1: Write `src/Models/Comment.php`**

```php
<?php

declare(strict_types=1);

namespace App\Models;

final class Comment
{
    public function __construct(
        public readonly int $id,
        public readonly int $postId,
        public readonly ?int $userId,
        public readonly string $comment,
        public readonly string $email,
        public readonly string $datecreated,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:          (int) $row['id'],
            postId:      (int) $row['post_id'],
            userId:      isset($row['user_id']) ? (int) $row['user_id'] : null,
            comment:     (string) $row['comment'],
            email:       (string) $row['email'],
            datecreated: (string) $row['datecreated'],
        );
    }
}
```

- [ ] **Step 2: Write `src/Repositories/CommentRepository.php`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Comment;
use PDO;

final class CommentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<Comment>
     */
    public function allForPost(int $postId): array
    {
        $sql = 'SELECT * FROM post_comments WHERE post_id = :post_id ORDER BY datecreated DESC';
        $stm = $this->pdo->prepare($sql);
        $stm->execute(['post_id' => $postId]);
        return array_map(Comment::fromRow(...), $stm->fetchAll());
    }

    /**
     * @param array{post_id: int, user_id: int, comment: string, email: string} $data
     */
    public function save(array $data): int
    {
        $sql = 'INSERT INTO post_comments (post_id, user_id, comment, email, datecreated) '
             . 'VALUES (:post_id, :user_id, :comment, :email, NOW())';
        $stm = $this->pdo->prepare($sql);
        $stm->execute([
            'post_id' => $data['post_id'],
            'user_id' => $data['user_id'],
            'comment' => $data['comment'],
            'email'   => $data['email'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Models/Comment.php src/Repositories/CommentRepository.php
git commit -m "comments: add Comment DTO + CommentRepository (post_comments table)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.8: AuthService (extract verifyLogin/verifySignup logic)

**Files:**
- Create: `src/Services/AuthService.php`
- Create: `src/Services/AuthResult.php`

**Interfaces:**
- Consumes: `App\Repositories\UserRepository`
- Produces:
  - `App\Services\AuthResult` value object with `bool $success`, `string $message`, `?User $user`.
  - `App\Services\AuthService::verifyLogin(string $email, string $password, string $token, string $sessionToken): AuthResult`
  - `AuthService::verifySignup(string $email, string $password, string $token, string $sessionToken): AuthResult`
  - `AuthService::createUser(string $username, string $email, string $password): User` — hashes password with `PASSWORD_DEFAULT`.

- [ ] **Step 1: Write `src/Services/AuthResult.php`**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?User $user = null,
    ) {
    }

    public static function failure(string $message): self
    {
        return new self(false, $message, null);
    }

    public static function success(string $message, ?User $user = null): self
    {
        return new self(true, $message, $user);
    }
}
```

- [ ] **Step 2: Write `src/Services/AuthService.php`**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function verifyLogin(string $email, string $password, string $token, string $sessionToken): AuthResult
    {
        if (!hash_equals($sessionToken, $token)) {
            return AuthResult::failure('TOKEN MISMATCH');
        }
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            return AuthResult::failure('WRONG EMAIL');
        }
        if (strlen($password) < 6) {
            return AuthResult::failure('PASSWORD TOO SHORT');
        }
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return AuthResult::failure('USER NOT FOUND');
        }
        if (!password_verify($password, $user->password)) {
            return AuthResult::failure('WRONG PASSWORD');
        }
        return AuthResult::success('LOGGED IN', $user);
    }

    public function verifySignup(string $email, string $password, string $token, string $sessionToken): AuthResult
    {
        if (!hash_equals($sessionToken, $token)) {
            return AuthResult::failure('TOKEN MISMATCH');
        }
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            return AuthResult::failure('WRONG EMAIL');
        }
        if (strlen($password) < 6) {
            return AuthResult::failure('PASSWORD TOO SHORT');
        }
        if ($this->users->findByEmail($email) !== null) {
            return AuthResult::failure('USER ALREADY EXISTS');
        }
        return AuthResult::success('SIGNUP OK');
    }

    public function createUser(string $username, string $email, string $password): User
    {
        $id = $this->users->save([
            'username' => $username,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return new User(
            id: $id,
            username: $username,
            email: $email,
            password: '',
            roletype: 'user',
        );
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Services/
git commit -m "auth: extract AuthService + AuthResult from LoginController

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.9: Typed `View` renderer + `Response` + `Request`

**Files:**
- Create: `src/Support/View.php`
- Create: `src/Http/Request.php`
- Create: `src/Http/Response.php`

**Interfaces:**
- Consumes: nothing
- Produces:
  - `App\Support\View::render(string $template, array<string, mixed> $data = []): string` — renders `resources/views/{template}.tpl.php` via output buffering.
  - `App\Http\Request::fromGlobals(): Request` with typed getters `method(): string`, `uri(): string`, `post(string $key, string $default = ''): string`, `header(string $name): ?string`.
  - `App\Http\Response::redirect(string $url): never` and `Response::json(array $data, int $status = 200): never`.

- [ ] **Step 1: Write `src/Support/View.php`**

```php
<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class View
{
    public function __construct(private readonly string $viewsDir)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->viewsDir . '/' . $template . '.tpl.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$file}");
        }
        extract($data, EXTR_OVERWRITE);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
```

- [ ] **Step 2: Write `src/Http/Request.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, string>            $query
     * @param array<string, string|array<int,string>> $post
     * @param array<string, string>            $headers
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
```

- [ ] **Step 3: Write `src/Http/Response.php`**

```php
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
```

- [ ] **Step 4: Commit**

```bash
git add src/Support/View.php src/Http/Request.php src/Http/Response.php
git commit -m "http: add typed View renderer, Request, Response

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.10: Controllers — Base, Post, Auth

**Files:**
- Create: `src/Controllers/BaseController.php`
- Create: `src/Controllers/PostController.php`
- Create: `src/Controllers/AuthController.php`

**Interfaces:**
- Consumes: `App\Support\View`, `App\Repositories\{PostRepository,CommentRepository,UserRepository}`, `App\Services\AuthService`, `App\Http\{Request,Response}`.
- Produces:
  - `BaseController::display(string $layout = 'default', array $data = []): never` — wraps `$this->content` with the layout and echoes.
  - `PostController::{index,show,create,edit,save,saveComment,delete}` matching legacy URL surface.
  - `AuthController::{showLogin,showSignup,login,signup,logout}` matching legacy URLs.

- [ ] **Step 1: Write `src/Controllers/BaseController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\View;

abstract class BaseController
{
    protected string $content = '';

    public function __construct(protected readonly View $view)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function display(string $layout = 'layouts/default', array $data = []): void
    {
        $data['content'] = $this->content;
        echo $this->view->render($layout, $data);
    }
}
```

- [ ] **Step 2: Write `src/Controllers/PostController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Support\View;

final class PostController extends BaseController
{
    public function __construct(
        View $view,
        private readonly PostRepository $posts,
        private readonly CommentRepository $comments,
        private readonly Request $request,
    ) {
        parent::__construct($view);
    }

    public function index(): void
    {
        $this->content = $this->view->render('pages/posts/index', ['posts' => $this->posts->all()]);
    }

    public function show(string $id): void
    {
        $postId = (int) $id;
        $post   = $this->posts->findById($postId);
        if ($post === null) {
            http_response_code(404);
            $this->content = $this->view->render('pages/errors/404');
            return;
        }
        $this->content = $this->view->render('pages/posts/show', [
            'post'     => $post,
            'comments' => $this->comments->allForPost($postId),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->content = $this->view->render('pages/posts/create');
    }

    public function edit(string $id): void
    {
        $this->requireLogin();
        $post = $this->posts->findById((int) $id);
        if ($post === null) {
            Response::redirect('/');
        }
        $this->content = $this->view->render('pages/posts/edit', ['post' => $post]);
    }

    public function save(?string $id = null): void
    {
        $this->requireLogin();
        $data = [
            'user_id' => (int) ($_SESSION['userData']['id'] ?? 0),
            'title'   => $this->request->postString('title'),
            'message' => $this->request->postString('message'),
        ];
        if ($id === null) {
            $this->posts->save($data);
        } else {
            $this->posts->update((int) $id, ['title' => $data['title'], 'message' => $data['message']]);
        }
        Response::redirect('/');
    }

    public function saveComment(string $id): void
    {
        $this->requireLogin();
        $this->comments->save([
            'post_id' => (int) $id,
            'user_id' => (int) ($_SESSION['userData']['id'] ?? 0),
            'email'   => $this->request->postString('email'),
            'comment' => $this->request->postString('comment'),
        ]);
        Response::redirect('/posts/' . $id);
    }

    public function delete(string $id): void
    {
        $this->requireLogin();
        $this->posts->delete((int) $id);
        Response::redirect('/');
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['loggedin'])) {
            Response::redirect('/auth/login');
        }
    }
}
```

- [ ] **Step 3: Write `src/Controllers/AuthController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;
use App\Support\View;

final class AuthController extends BaseController
{
    public function __construct(
        View $view,
        private readonly AuthService $auth,
        private readonly Request $request,
    ) {
        parent::__construct($view);
    }

    public function showLogin(): void
    {
        $this->content = $this->view->render('pages/auth/login', [
            'token'  => $this->csrfToken(),
            'signup' => false,
        ]);
    }

    public function showSignup(): void
    {
        $this->content = $this->view->render('pages/auth/login', [
            'token'  => $this->csrfToken(),
            'signup' => true,
        ]);
    }

    public function login(): void
    {
        $result = $this->auth->verifyLogin(
            email: $this->request->postString('email'),
            password: $this->request->postString('password'),
            token: $this->request->postString('_csrf'),
            sessionToken: (string) ($_SESSION['csrf'] ?? ''),
        );

        if ($result->success && $result->user !== null) {
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['userData'] = [
                'id'       => $result->user->id,
                'email'    => $result->user->email,
                'username' => $result->user->username,
                'roletype' => $result->user->roletype,
            ];
        } else {
            $_SESSION['message'] = $result->message;
        }

        if (strtoupper($this->request->header('x-requested-with') ?? '') === 'XMLHTTPREQUEST') {
            Response::json(['success' => $result->success, 'message' => $result->message]);
        }
        Response::redirect($result->success ? '/' : '/auth/login');
    }

    public function signup(): void
    {
        $email    = $this->request->postString('email');
        $password = $this->request->postString('password');
        $username = $this->request->postString('username');
        $result   = $this->auth->verifySignup(
            email: $email,
            password: $password,
            token: $this->request->postString('_csrf'),
            sessionToken: (string) ($_SESSION['csrf'] ?? ''),
        );

        if ($result->success) {
            $user = $this->auth->createUser($username !== '' ? $username : $email, $email, $password);
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['userData'] = ['id' => $user->id, 'email' => $user->email, 'username' => $user->username, 'roletype' => 'user'];
        } else {
            $_SESSION['message'] = $result->message;
        }

        if (strtoupper($this->request->header('x-requested-with') ?? '') === 'XMLHTTPREQUEST') {
            Response::json(['success' => $result->success, 'message' => $result->message]);
        }
        Response::redirect($result->success ? '/' : '/auth/signup');
    }

    public function logout(): void
    {
        $_SESSION = [];
        Response::redirect('/auth/login');
    }

    private function csrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf'] = $token;
        return $token;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Controllers/
git commit -m "controllers: add BaseController, PostController, AuthController (thin)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.11: Views — layouts + pages

**Files:**
- Create: `resources/views/layouts/default.tpl.php`
- Create: `resources/views/pages/posts/{index,show,create,edit}.tpl.php`
- Create: `resources/views/pages/auth/login.tpl.php`
- Create: `resources/views/pages/errors/404.tpl.php`
- Reference: `/Users/hidranarias/projects/udemy/freeblog/layout/index.tpl.php`, `app/views/*.tpl.php`

**Interfaces:**
- Consumes: variables passed via `View::render`. `default.tpl.php` expects `string $content`. `posts/index` expects `list<Post> $posts`. `posts/show` expects `Post $post, list<Comment> $comments`. `posts/edit` expects `Post $post`. `auth/login` expects `string $token, bool $signup`.
- Produces: rendered HTML.

- [ ] **Step 1: Copy & adapt layout**

Read `freeblog/layout/index.tpl.php`. Re-implement as `resources/views/layouts/default.tpl.php` with these changes: replace `<?= $this->getContent() ?>` with `<?= $content ?? '' ?>`; update CSS/JS paths to `/assets/css/...` and `/assets/js/...`.

- [ ] **Step 2: Port each page view**

For each of `posts.tpl.php`, `post.tpl.php`, `newpost.tpl.php`, `editpost.tpl.php`, `login.tpl.php` in `freeblog/app/views/`:
- Read the file.
- Create the corresponding new file (see mapping below).
- Update any property access (`$post->message`) to remain unchanged (DTOs use the same names where possible).
- Use `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` for any user-supplied output that the legacy view echoed raw.

Mapping:
- `posts.tpl.php`  → `resources/views/pages/posts/index.tpl.php`
- `post.tpl.php`   → `resources/views/pages/posts/show.tpl.php`
- `newpost.tpl.php`→ `resources/views/pages/posts/create.tpl.php`
- `editpost.tpl.php`→ `resources/views/pages/posts/edit.tpl.php`
- `login.tpl.php`  → `resources/views/pages/auth/login.tpl.php`

- [ ] **Step 3: Add a 404 view**

```php
<?php /* resources/views/pages/errors/404.tpl.php */ ?>
<h1>404 — Post not found</h1>
<p><a href="/">Back home</a></p>
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/
git commit -m "views: port layout + pages to resources/views (escape user output)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.12: Kernel + Container + public/index.php

**Files:**
- Create: `config/routes.php`
- Create: `config/app.php`
- Create: `src/Kernel.php`
- Create: `public/index.php`
- Create: `public/.htaccess`

**Interfaces:**
- Consumes: everything above.
- Produces: `App\Kernel::handle(): void` that boots env, container, dispatches the request, displays the controller output.

- [ ] **Step 1: Write `config/routes.php`**

```php
<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\PostController;

return [
    'GET' => [
        '/'                => [PostController::class, 'index'],
        'posts'            => [PostController::class, 'index'],
        'posts/create'     => [PostController::class, 'create'],
        'posts/:id'        => [PostController::class, 'show'],
        'posts/:id/edit'   => [PostController::class, 'edit'],
        'auth/login'       => [AuthController::class, 'showLogin'],
        'auth/signup'      => [AuthController::class, 'showSignup'],
        'healthz'          => [App\Controllers\HealthController::class, 'check'],
    ],
    'POST' => [
        'posts'                  => [PostController::class, 'save'],
        'posts/:id'              => [PostController::class, 'save'],
        'posts/:id/delete'       => [PostController::class, 'delete'],
        'posts/:id/comments'     => [PostController::class, 'saveComment'],
        'auth/login'             => [AuthController::class, 'login'],
        'auth/signup'            => [AuthController::class, 'signup'],
        'auth/logout'            => [AuthController::class, 'logout'],
    ],
];
```

> `HealthController` is added in Task 4.3. For Phase 1, comment that line out (`// 'healthz' => ...`). Task 4.3 step 2 uncomments it.

- [ ] **Step 2: Write `config/app.php`**

```php
<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'env'   => Env::string('APP_ENV', 'local'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url'   => Env::string('APP_URL', 'http://localhost:8080'),
    'paths' => [
        'views' => dirname(__DIR__) . '/resources/views',
    ],
];
```

- [ ] **Step 3: Write `src/Kernel.php`**

```php
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
```

- [ ] **Step 4: Write `public/index.php`**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

(new App\Kernel(dirname(__DIR__)))->handle();
```

- [ ] **Step 5: Write `public/.htaccess`**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

- [ ] **Step 6: Smoke check syntax**

Run: `php -l public/index.php && php -l src/Kernel.php && php -l config/routes.php`
Expected: three `No syntax errors detected` lines.

- [ ] **Step 7: Commit**

```bash
git add config/ src/Kernel.php public/
git commit -m "kernel: add Kernel, container wiring, routes config, public entry

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.13: Initial SQL migration

**Files:**
- Create: `database/migrations/0001_init.sql`
- Create: `database/seeds/0001_demo.sql`

**Interfaces:**
- Consumes: nothing
- Produces: schema for `users`, `posts`, `post_comments` (renamed from `postscomments`), charset `utf8mb4`, FKs on cascade. Idempotent: every statement is `CREATE TABLE IF NOT EXISTS`.

- [ ] **Step 1: Write `database/migrations/0001_init.sql`**

```sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username  VARCHAR(64) NOT NULL,
    email     VARCHAR(128) NOT NULL,
    password  VARCHAR(255) NOT NULL,
    roletype  ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_email (email),
    KEY idx_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
    id           INT NOT NULL AUTO_INCREMENT,
    title        VARCHAR(255) NOT NULL,
    message      TEXT NOT NULL,
    datecreated  DATETIME NOT NULL,
    user_id      INT NOT NULL,
    PRIMARY KEY (id),
    KEY idx_posts_title (title),
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_comments (
    id           INT NOT NULL AUTO_INCREMENT,
    post_id      INT NOT NULL,
    user_id      INT DEFAULT NULL,
    comment      TEXT NOT NULL,
    email        VARCHAR(128) NOT NULL,
    datecreated  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_comments_post (post_id),
    CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Write `database/seeds/0001_demo.sql`**

```sql
INSERT INTO users (username, email, password, roletype)
VALUES ('demo', 'demo@example.com', '$2y$10$ll0E9Q4uH71m3UJj3DTzC.4zvhh1V0wKmuKtFnhMOYZAxhPVF5/Hi', 'admin')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO posts (title, message, datecreated, user_id)
VALUES ('Hello world', 'First post', NOW(), 1)
ON DUPLICATE KEY UPDATE id = id;
```

(Password hash above corresponds to `demopass` — a demo-only credential.)

- [ ] **Step 3: Commit**

```bash
git add database/
git commit -m "db: initial migration (utf8mb4, post_comments rename, FKs)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.14: `bin/console migrate` runner

**Files:**
- Create: `bin/console`
- Create: `src/Console/MigrateCommand.php`
- Create: `src/Console/Application.php`

**Interfaces:**
- Consumes: `App\Database\ConnectionFactory`
- Produces: `php bin/console migrate` applies pending `database/migrations/*.sql` in lex order, tracks them in a `migrations(filename PK)` table, transaction per file. `php bin/console migrate --fresh` drops all app tables then re-applies.

- [ ] **Step 1: Write `src/Console/Application.php`**

```php
<?php

declare(strict_types=1);

namespace App\Console;

final class Application
{
    /** @var array<string, callable(array<int, string>): int> */
    private array $commands = [];

    public function register(string $name, callable $handler): void
    {
        $this->commands[$name] = $handler;
    }

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'help';
        if (!isset($this->commands[$name])) {
            fwrite(STDERR, "Unknown command: {$name}\n");
            fwrite(STDERR, "Available: " . implode(', ', array_keys($this->commands)) . "\n");
            return 1;
        }
        return ($this->commands[$name])(array_slice($argv, 2));
    }
}
```

- [ ] **Step 2: Write `src/Console/MigrateCommand.php`**

```php
<?php

declare(strict_types=1);

namespace App\Console;

use PDO;
use RuntimeException;

final class MigrateCommand
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsDir,
    ) {
    }

    /**
     * @param array<int, string> $args
     */
    public function __invoke(array $args): int
    {
        if (in_array('--fresh', $args, true)) {
            $this->dropAll();
        }

        $this->ensureTracker();
        $applied = $this->appliedSet();
        $files   = $this->files();

        $pending = array_filter($files, static fn(string $f) => !isset($applied[basename($f)]));
        if ($pending === []) {
            fwrite(STDOUT, "No pending migrations.\n");
            return 0;
        }

        foreach ($pending as $file) {
            $this->apply($file);
        }
        fwrite(STDOUT, "Applied " . count($pending) . " migration(s).\n");
        return 0;
    }

    private function ensureTracker(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (filename VARCHAR(255) PRIMARY KEY, applied_at DATETIME NOT NULL) '
            . 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * @return array<string, true>
     */
    private function appliedSet(): array
    {
        $rows = $this->pdo->query('SELECT filename FROM migrations')?->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_fill_keys($rows, true);
    }

    /**
     * @return list<string>
     */
    private function files(): array
    {
        $files = glob($this->migrationsDir . '/*.sql') ?: [];
        sort($files);
        return $files;
    }

    private function apply(string $file): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Cannot read {$file}");
        }
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec($sql);
            $stm = $this->pdo->prepare('INSERT INTO migrations (filename, applied_at) VALUES (:f, NOW())');
            $stm->execute(['f' => basename($file)]);
            $this->pdo->commit();
            fwrite(STDOUT, "Applied: " . basename($file) . "\n");
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function dropAll(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $this->pdo->exec('DROP TABLE IF EXISTS post_comments, posts, users, migrations');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        fwrite(STDOUT, "Dropped all tables.\n");
    }
}
```

- [ ] **Step 3: Write `bin/console`**

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (is_file($root . '/.env')) {
    Dotenv\Dotenv::createImmutable($root)->load();
}

$pdo = App\Database\ConnectionFactory::fromEnv();
$app = new App\Console\Application();
$app->register('migrate', new App\Console\MigrateCommand($pdo, $root . '/database/migrations'));

exit($app->run($argv));
```

- [ ] **Step 4: Make executable**

Run: `chmod +x bin/console`

- [ ] **Step 5: Commit**

```bash
git add bin/console src/Console/
git commit -m "console: add bin/console + MigrateCommand (forward-only, tracked)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 1.15: PSR-7/15 upgrade — league/route + nyholm/psr7

> **Foundation epilogue.** Tasks 1.1–1.14 built the from-scratch HTTP layer so students *understand* routing, requests, and responses. This task swaps the hand-rolled pieces for the PSR-7/15 industry standard, preserving every URL and the legacy behavior. The course narrative becomes "we built it once to learn — now we adopt the standard, and because we understood it, it isn't magic."

**Files:**
- Modify: `composer.json` (add `league/route ^6.2`, `nyholm/psr7 ^1.8`, `nyholm/psr7-server ^1.1`), `composer.lock` (regenerated)
- Delete: `src/Http/Request.php`, `src/Http/Response.php` (replaced by PSR-7 interfaces; the historical originals remain checkout-able at `lesson-1-9`)
- Replace: `src/Http/Router.php` — now thin wrapper over `League\Route\Router` that accepts our `config/routes.php` shape
- Modify: `src/Kernel.php` — becomes a PSR-15 `RequestHandlerInterface`: builds a `ServerRequestInterface` from globals via `nyholm/psr7-server`, dispatches via `League\Route\Router::handle(ServerRequestInterface)`, emits the returned `ResponseInterface`
- Modify: `src/Controllers/BaseController.php` — drop the legacy `$content`/`display()` shape; add a protected `respond(string $body, int $status = 200, array $headers = []): ResponseInterface` helper that wraps a page fragment with the layout; add `redirect(string $url): ResponseInterface` and `json(array $data, int $status = 200): ResponseInterface` helpers
- Modify: `src/Controllers/PostController.php`, `src/Controllers/AuthController.php` — each public method now: `public function index(ServerRequestInterface $request, array $params = []): ResponseInterface`. Returns built via `$this->respond(...)` / `$this->redirect(...)` / `$this->json(...)`.
- Modify: `src/Controllers/HealthController.php` placeholder forward-declared (Task 4.3 will implement; for now leave a stub with the PSR-15 signature) — actually skip if HealthController doesn't exist yet (Task 4.3 creates it with the PSR-7 signature directly)
- Modify: `config/routes.php` — same shape (`[Controller::class, 'method']`); no changes to the route table itself
- Modify: `tests/Unit/Http/RouterTest.php` — adapt assertions so the router returns a configured `League\Route\Router` instance OR test that `dispatch(ServerRequest)` produces a `ResponseInterface` with the right body for known routes; pick the simpler shape

**Interfaces:**
- Consumes: PSR-7 `ServerRequestInterface`, PSR-7 `ResponseInterface`, PSR-17 factories (`Psr17Factory` from nyholm/psr7), PSR-15 `RequestHandlerInterface`
- Produces:
  - `App\Http\Router::__construct(array $routes)` — same outward signature
  - `App\Http\Router::handle(ServerRequestInterface $request): ResponseInterface` (PSR-15 contract)
  - `App\Kernel::handle(): void` unchanged at the call site; internally now PSR-7/15
  - Controllers: `(ServerRequestInterface $request, array $params = []): ResponseInterface`

- [ ] **Step 1: Add Composer dependencies**

```bash
composer require league/route:^6.2 nyholm/psr7:^1.8 nyholm/psr7-server:^1.1
```

- [ ] **Step 2: Delete legacy Http\Request and Http\Response**

```bash
git rm src/Http/Request.php src/Http/Response.php
```

The historical implementations remain checkout-able at tag `lesson-1-9`.

- [ ] **Step 3: Rewrite `src/Http/Router.php`**

```php
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
                $this->router->map(strtoupper($method), $normalized, [$class, $action]);
            }
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->handle($request);
    }
}
```

- [ ] **Step 4: Rewrite `src/Kernel.php` as a PSR-15 dispatcher**

```php
<?php

declare(strict_types=1);

namespace App;

use App\Database\ConnectionFactory;
use App\Http\Router;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\View;
use Dotenv\Dotenv;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\Http\Exception\NotFoundException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        $request   = $this->buildRequest();

        try {
            $response = $container->get(Router::class)->handle($request);
        } catch (NotFoundException) {
            $response = (new Psr17Factory())->createResponse(404)
                ->withHeader('Content-Type', 'text/html; charset=utf-8');
            $response->getBody()->write('<h1>404 Not Found</h1>');
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

    private function buildContainer(): Container
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer(cacheResolutions: true));

        $container->addShared(PDO::class, ConnectionFactory::fromEnv(...));
        $container->addShared(View::class, fn() => new View($this->basePath . '/resources/views'));

        $container->add(PostRepository::class)->addArgument(PDO::class);
        $container->add(UserRepository::class)->addArgument(PDO::class);
        $container->add(CommentRepository::class)->addArgument(PDO::class);
        $container->add(AuthService::class)->addArgument(UserRepository::class);

        $routes = require $this->basePath . '/config/routes.php';
        $container->addShared(Router::class, fn() => new Router($routes, $container));

        return $container;
    }

    private function buildRequest(): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        return (new ServerRequestCreator($factory, $factory, $factory, $factory))
            ->fromGlobals();
    }

    private function emit(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false);
            }
        }
        echo $response->getBody();
    }
}
```

- [ ] **Step 5: Rewrite `src/Controllers/BaseController.php`**

```php
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
```

- [ ] **Step 6: Rewrite `src/Controllers/PostController.php`**

Each method now: `public function METHOD(ServerRequestInterface $request, array $args = []): ResponseInterface`. Use `$args['id']` for parameterized routes (league/route passes route placeholders as the second arg's associative array by default with ApplicationStrategy). Use `$request->getParsedBody()` (after `nyholm/psr7-server` parsing) for POST data; for our usage, fall back to `$_POST` for now via the shim `$post = (array) ($request->getParsedBody() ?? $_POST);`.

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Support\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PostController extends BaseController
{
    public function __construct(
        View $view,
        private readonly PostRepository $posts,
        private readonly CommentRepository $comments,
    ) {
        parent::__construct($view);
    }

    public function index(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        return $this->respond($this->view->render('pages/posts/index', ['posts' => $this->posts->all()]));
    }

    public function show(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $post = $this->posts->findById((int) ($args['id'] ?? 0));
        if ($post === null) {
            return $this->respond($this->view->render('pages/errors/404'), 404);
        }
        return $this->respond($this->view->render('pages/posts/show', [
            'post'     => $post,
            'comments' => $this->comments->allForPost($post->id),
        ]));
    }

    public function create(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        return $this->respond($this->view->render('pages/posts/create'));
    }

    public function edit(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        $post = $this->posts->findById((int) ($args['id'] ?? 0));
        if ($post === null) {
            return $this->redirect('/');
        }
        return $this->respond($this->view->render('pages/posts/edit', ['post' => $post]));
    }

    public function save(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        $post = (array) ($request->getParsedBody() ?? $_POST);
        $data = [
            'user_id' => (int) ($_SESSION['userData']['id'] ?? 0),
            'title'   => (string) ($post['title'] ?? ''),
            'message' => (string) ($post['message'] ?? ''),
        ];
        if (isset($args['id'])) {
            $this->posts->update((int) $args['id'], ['title' => $data['title'], 'message' => $data['message']]);
        } else {
            $this->posts->save($data);
        }
        return $this->redirect('/');
    }

    public function saveComment(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        $post = (array) ($request->getParsedBody() ?? $_POST);
        $postId = (int) ($args['id'] ?? 0);
        $this->comments->save([
            'post_id' => $postId,
            'user_id' => (int) ($_SESSION['userData']['id'] ?? 0),
            'email'   => (string) ($post['email'] ?? ''),
            'comment' => (string) ($post['comment'] ?? ''),
        ]);
        return $this->redirect('/posts/' . $postId);
    }

    public function delete(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        $this->posts->delete((int) ($args['id'] ?? 0));
        return $this->redirect('/');
    }
}
```

- [ ] **Step 7: Rewrite `src/Controllers/AuthController.php`**

Same shape — each method returns `ResponseInterface`. CSRF still in `$_SESSION['csrf']`. XHR detection via `$request->getHeaderLine('x-requested-with')` (case-insensitive in PSR-7).

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Support\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends BaseController
{
    public function __construct(
        View $view,
        private readonly AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function showLogin(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        return $this->respond($this->view->render('pages/auth/login', [
            'token'  => $this->csrfToken(),
            'signup' => false,
        ]));
    }

    public function showSignup(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        return $this->respond($this->view->render('pages/auth/login', [
            'token'  => $this->csrfToken(),
            'signup' => true,
        ]));
    }

    public function login(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $post = (array) ($request->getParsedBody() ?? $_POST);
        $result = $this->auth->verifyLogin(
            email: (string) ($post['email'] ?? ''),
            password: (string) ($post['password'] ?? ''),
            token: (string) ($post['_csrf'] ?? ''),
            sessionToken: (string) ($_SESSION['csrf'] ?? ''),
        );

        if ($result->success && $result->user !== null) {
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['userData'] = [
                'id'       => $result->user->id,
                'email'    => $result->user->email,
                'username' => $result->user->username,
                'roletype' => $result->user->roletype,
            ];
        } else {
            $_SESSION['message'] = $result->message;
        }

        if (strtoupper($request->getHeaderLine('x-requested-with')) === 'XMLHTTPREQUEST') {
            return $this->json(['success' => $result->success, 'message' => $result->message]);
        }
        return $this->redirect($result->success ? '/' : '/auth/login');
    }

    public function signup(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $post = (array) ($request->getParsedBody() ?? $_POST);
        $email    = (string) ($post['email'] ?? '');
        $password = (string) ($post['password'] ?? '');
        $username = (string) ($post['username'] ?? '');
        $result = $this->auth->verifySignup(
            email: $email,
            password: $password,
            token: (string) ($post['_csrf'] ?? ''),
            sessionToken: (string) ($_SESSION['csrf'] ?? ''),
        );

        if ($result->success) {
            $user = $this->auth->createUser($username !== '' ? $username : $email, $email, $password);
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['userData'] = [
                'id'       => $user->id,
                'email'    => $user->email,
                'username' => $user->username,
                'roletype' => 'user',
            ];
        } else {
            $_SESSION['message'] = $result->message;
        }

        if (strtoupper($request->getHeaderLine('x-requested-with')) === 'XMLHTTPREQUEST') {
            return $this->json(['success' => $result->success, 'message' => $result->message]);
        }
        return $this->redirect($result->success ? '/' : '/auth/signup');
    }

    public function logout(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $_SESSION = [];
        return $this->redirect('/auth/login');
    }

    private function csrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf'] = $token;
        return $token;
    }
}
```

- [ ] **Step 8: Update `tests/Unit/Http/RouterTest.php`**

The new Router is league/route-backed and needs a Container. Simplest test: assert that dispatching a known route returns a `ResponseInterface` with status 200 and that an unknown route throws `League\Route\Http\Exception\NotFoundException`. Wire a tiny test container with a stub controller that returns `new Response(200, [], 'hello')`. Aim for 3 focused tests; the granular regex behaviors are now league/route's responsibility.

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Router;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\Http\Exception\NotFoundException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StubController
{
    public function hello(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        return new Response(200, [], 'hello ' . ($args['name'] ?? 'world'));
    }
}

final class RouterTest extends TestCase
{
    private function router(): Router
    {
        $c = new Container();
        $c->delegate(new ReflectionContainer(cacheResolutions: true));
        return new Router([
            'GET' => ['/' => [StubController::class, 'hello'], 'hello/{name}' => [StubController::class, 'hello']],
        ], $c);
    }

    private function request(string $method, string $uri): ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest($method, $uri);
    }

    public function testExactRouteReturnsHandlerResponse(): void
    {
        $response = $this->router()->handle($this->request('GET', '/'));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('hello world', (string) $response->getBody());
    }

    public function testParameterizedRouteExtractsArg(): void
    {
        $response = $this->router()->handle($this->request('GET', '/hello/anna'));
        self::assertSame('hello anna', (string) $response->getBody());
    }

    public function testUnknownRouteThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->router()->handle($this->request('GET', '/nope'));
    }
}
```

- [ ] **Step 9: Smoke check**

```bash
composer install
php -l src/Kernel.php src/Http/Router.php src/Controllers/BaseController.php src/Controllers/PostController.php src/Controllers/AuthController.php
vendor/bin/phpunit --no-configuration tests/Unit/Http/RouterTest.php
```

- [ ] **Step 10: Commit**

```bash
git add composer.json composer.lock src/Http/ src/Kernel.php src/Controllers/ tests/Unit/Http/RouterTest.php
git commit -m "http: swap hand-rolled Router + Request/Response for PSR-7/15 (league/route + nyholm/psr7)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

- [ ] **Step A — Tag**: `git tag lesson-1-15`
- [ ] **Step B — Lesson**: at `../phpblog-udemy/01-foundation/01-15-psr7-15-upgrade.md` per template. The "why" MUST cover: why PSRs exist (interop), what PSR-7/15/17 each specify, why we DIDN'T start with PSR-7 (pedagogy of the from-scratch lessons), the cost/benefit of league/route over fast-route, and the middleware door this opens (which we won't use here but the architecture now supports).
- [ ] **Step C — Lesson commit**: per the Course companion section above.

---

## Phase 2 — Quality gates

### Task 2.1: PHP_CodeSniffer config + first-pass clean

**Files:**
- Create: `phpcs.xml`

**Interfaces:**
- Produces: `composer cs` and `composer cs:fix` work.

- [ ] **Step 1: Write `phpcs.xml`**

```xml
<?xml version="1.0"?>
<ruleset name="phpenterpriseblog">
    <description>PSR-12 + Slevomat strict typing</description>
    <file>src</file>
    <file>bin</file>
    <file>config</file>
    <file>public</file>
    <file>tests</file>
    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/resources/views/*</exclude-pattern>
    <exclude-pattern>*/database/*</exclude-pattern>

    <arg name="colors"/>
    <arg value="np"/>

    <rule ref="PSR12"/>
    <rule ref="SlevomatCodingStandard.TypeHints.ParameterTypeHint"/>
    <rule ref="SlevomatCodingStandard.TypeHints.ReturnTypeHint"/>
    <rule ref="SlevomatCodingStandard.TypeHints.PropertyTypeHint"/>
    <rule ref="SlevomatCodingStandard.TypeHints.DeclareStrictTypes">
        <properties>
            <property name="newlinesCountBetweenOpenTagAndDeclare" value="2"/>
            <property name="newlinesCountAfterDeclare" value="2"/>
        </properties>
    </rule>
</ruleset>
```

- [ ] **Step 2: Run cs and auto-fix anything safe**

Run: `composer cs:fix || true`
Then: `composer cs`
Expected: zero violations after the auto-fixer. If any remain, fix them by hand and re-run.

- [ ] **Step 3: Commit**

```bash
git add phpcs.xml src/ bin/ config/ public/ tests/
git commit -m "cs: add PHP_CodeSniffer config and clean PSR-12 + strict typing violations

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 2.2: PHPStan config + baseline at level 6

**Files:**
- Create: `phpstan.neon`
- Create: `phpstan-baseline.neon` (generated)

**Interfaces:**
- Produces: `composer stan` exits 0 against `phpstan-baseline.neon`.

- [ ] **Step 1: Write `phpstan.neon`**

```neon
includes:
  - phpstan-baseline.neon
parameters:
  level: 6
  paths:
    - src
    - bin
    - config
    - public
    - tests
  bootstrapFiles:
    - vendor/autoload.php
  excludePaths:
    - vendor
    - resources/views
    - database
```

- [ ] **Step 2: Generate baseline**

Run: `vendor/bin/phpstan analyse --memory-limit=512M --generate-baseline=phpstan-baseline.neon`
Expected: exit 0 (everything starts in baseline).

- [ ] **Step 3: Confirm steady-state passes**

Run: `composer stan`
Expected: `[OK] No errors`.

- [ ] **Step 4: Commit**

```bash
git add phpstan.neon phpstan-baseline.neon
git commit -m "stan: add PHPStan level 6 with starting baseline

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 2.3: Rector one-shot UP_TO_PHP_85 sweep

**Files:**
- Create: `rector.php`

**Interfaces:**
- Produces: `composer rector` (dry-run) and `composer rector:fix` (applies).

- [ ] **Step 1: Write `rector.php`**

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/bin', __DIR__ . '/config', __DIR__ . '/tests'])
    ->withSets([
        LevelSetList::UP_TO_PHP_85,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ]);
```

- [ ] **Step 2: Dry-run**

Run: `composer rector`
Expected: outputs proposed changes (often zero on greenfield code).

- [ ] **Step 3: Apply if changes are safe**

If dry-run is clean, skip. Otherwise: `composer rector:fix`, review diff, run `composer ci`.

- [ ] **Step 4: Commit**

```bash
git add rector.php
git diff --quiet || git add -A
git commit -m "rector: add UP_TO_PHP_85 sweep config

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Phase 3 — Unit tests

### Task 3.1: PHPUnit config + bootstrap

**Files:**
- Create: `phpunit.xml.dist`
- Create: `tests/bootstrap.php`

- [ ] **Step 1: Write `phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         displayDetailsOnTestsThatTriggerWarnings="true"
         failOnWarning="true"
         failOnRisky="true">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 2: Write `tests/bootstrap.php`**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (is_file(dirname(__DIR__) . '/.env')) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
}
```

- [ ] **Step 3: Run existing tests**

Run: `composer test`
Expected: RouterTest passes (4 tests). PHPUnit finds the suite.

- [ ] **Step 4: Commit**

```bash
git add phpunit.xml.dist tests/bootstrap.php
git commit -m "phpunit: add config, bootstrap, two suites

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 3.2: AuthService unit tests with mocked UserRepository

**Files:**
- Create: `tests/Unit/Services/AuthServiceTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private function user(string $email = 'a@b.co', string $rawPassword = 'secret123'): User
    {
        return new User(
            id: 1,
            username: 'demo',
            email: $email,
            password: password_hash($rawPassword, PASSWORD_DEFAULT),
            roletype: 'user',
        );
    }

    public function testLoginRejectsTokenMismatch(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $svc  = new AuthService($repo);
        $r    = $svc->verifyLogin('a@b.co', 'secret123', 'bad', 'good');
        self::assertFalse($r->success);
        self::assertSame('TOKEN MISMATCH', $r->message);
    }

    public function testLoginRejectsBadEmail(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $svc  = new AuthService($repo);
        self::assertSame('WRONG EMAIL', $svc->verifyLogin('nope', 'secret123', 't', 't')->message);
    }

    public function testLoginRejectsShortPassword(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $svc  = new AuthService($repo);
        self::assertSame('PASSWORD TOO SHORT', $svc->verifyLogin('a@b.co', '123', 't', 't')->message);
    }

    public function testLoginRejectsUnknownUser(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByEmail')->willReturn(null);
        $svc = new AuthService($repo);
        self::assertSame('USER NOT FOUND', $svc->verifyLogin('a@b.co', 'secret123', 't', 't')->message);
    }

    public function testLoginRejectsBadPassword(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByEmail')->willReturn($this->user(rawPassword: 'correct123'));
        $svc = new AuthService($repo);
        self::assertSame('WRONG PASSWORD', $svc->verifyLogin('a@b.co', 'wrong123', 't', 't')->message);
    }

    public function testLoginSucceedsWithCorrectPassword(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByEmail')->willReturn($this->user(rawPassword: 'correct123'));
        $svc = new AuthService($repo);
        $r   = $svc->verifyLogin('a@b.co', 'correct123', 't', 't');
        self::assertTrue($r->success);
        self::assertNotNull($r->user);
    }

    public function testSignupRejectsExistingEmail(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByEmail')->willReturn($this->user());
        $svc = new AuthService($repo);
        self::assertSame('USER ALREADY EXISTS', $svc->verifySignup('a@b.co', 'secret123', 't', 't')->message);
    }
}
```

- [ ] **Step 2: Run**

Run: `composer test`
Expected: all RouterTest + AuthServiceTest tests pass (11 total).

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/Services/AuthServiceTest.php
git commit -m "test(auth): cover AuthService outcomes with mocked repo

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 3.3: Regression test for the `PostController::save` bug

**Files:**
- Create: `tests/Unit/Repositories/PostRepositorySaveRegressionTest.php`

**Why this matters:** the legacy code wrote `email` into the `message` column. The new code does not. This test pins the contract.

- [ ] **Step 1: Write the test using a PDO spy**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\PostRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class PostRepositorySaveRegressionTest extends TestCase
{
    public function testSavePassesMessageNotEmailToTheMessageColumn(): void
    {
        $stm = $this->createMock(PDOStatement::class);
        $stm->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params): bool {
                self::assertSame('actual message body', $params['message']);
                self::assertSame('actual title', $params['title']);
                self::assertSame(7, $params['user_id']);
                self::assertArrayNotHasKey('email', $params, 'PostRepository::save MUST NOT write email into message');
                return true;
            }));

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stm);
        $pdo->method('lastInsertId')->willReturn('99');

        $repo = new PostRepository($pdo);
        $id   = $repo->save([
            'user_id' => 7,
            'title'   => 'actual title',
            'message' => 'actual message body',
        ]);

        self::assertSame(99, $id);
    }
}
```

- [ ] **Step 2: Run**

Run: `composer test`
Expected: 12 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/Repositories/PostRepositorySaveRegressionTest.php
git commit -m "test(posts): regression-pin save() against legacy message-vs-email bug

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 3.4: View renderer test

**Files:**
- Create: `tests/Unit/Support/ViewTest.php`
- Create: `tests/Fixtures/views/hello.tpl.php`

- [ ] **Step 1: Create the fixture**

`tests/Fixtures/views/hello.tpl.php`:
```php
Hello, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>!
```

- [ ] **Step 2: Write the test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\View;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewTest extends TestCase
{
    private View $view;

    protected function setUp(): void
    {
        $this->view = new View(__DIR__ . '/../../Fixtures/views');
    }

    public function testRenderInterpolatesData(): void
    {
        self::assertSame('Hello, World!', trim($this->view->render('hello', ['name' => 'World'])));
    }

    public function testRenderEscapesHtml(): void
    {
        self::assertStringContainsString('&lt;script&gt;', $this->view->render('hello', ['name' => '<script>']));
    }

    public function testMissingViewThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->view->render('missing', []);
    }
}
```

- [ ] **Step 3: Run**

Run: `composer test`
Expected: 15 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Unit/Support/ViewTest.php tests/Fixtures/views/hello.tpl.php
git commit -m "test(view): cover render, escaping, missing template

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 3.5: Env accessor tests

**Files:**
- Create: `tests/Unit/Support/EnvTest.php`

- [ ] **Step 1: Write**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Env;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnvTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['TEST_VAR']);
    }

    public function testStringReadsFromEnvSuper(): void
    {
        $_ENV['TEST_VAR'] = 'hello';
        self::assertSame('hello', Env::string('TEST_VAR'));
    }

    public function testStringFallsBackToDefault(): void
    {
        self::assertSame('default', Env::string('MISSING_VAR', 'default'));
    }

    public function testStringThrowsOnMissingWithoutDefault(): void
    {
        $this->expectException(RuntimeException::class);
        Env::string('TOTALLY_MISSING_VAR');
    }

    public function testIntCoercion(): void
    {
        $_ENV['TEST_VAR'] = '42';
        self::assertSame(42, Env::int('TEST_VAR'));
    }

    public function testIntThrowsOnNonInt(): void
    {
        $_ENV['TEST_VAR'] = 'nope';
        $this->expectException(RuntimeException::class);
        Env::int('TEST_VAR');
    }

    public function testBoolTrueValues(): void
    {
        foreach (['1', 'true', 'yes', 'on'] as $v) {
            $_ENV['TEST_VAR'] = $v;
            self::assertTrue(Env::bool('TEST_VAR'));
        }
    }

    public function testBoolDefaultUsedWhenMissing(): void
    {
        self::assertTrue(Env::bool('STILL_MISSING_VAR', true));
        self::assertFalse(Env::bool('STILL_MISSING_VAR', false));
    }
}
```

- [ ] **Step 2: Run and commit**

```bash
composer test
git add tests/Unit/Support/EnvTest.php
git commit -m "test(env): cover Env::{string,int,bool}

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Phase 4 — Local dev image

### Task 4.1: Dockerfile

**Files:**
- Create: `deploy/docker/Dockerfile`
- Create: `deploy/docker/php.ini`
- Create: `deploy/docker/php-fpm.conf`
- Create: `deploy/docker/preload.php`
- Create: `deploy/docker/nginx.conf`
- Create: `.dockerignore`

- [ ] **Step 1: Write `.dockerignore`**

```
.git
.idea
node_modules
vendor
tests
docs
.env
.env.*
*.md
freeblog.sql
```

- [ ] **Step 2: Write `deploy/docker/Dockerfile`**

```dockerfile
# syntax=docker/dockerfile:1.7

# ---- stage 1: composer ----
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# ---- stage 2: runtime ----
FROM php:8.5-fpm-alpine AS runtime
RUN apk add --no-cache icu-libs oniguruma \
 && apk add --no-cache --virtual .build icu-dev oniguruma-dev linux-headers $PHPIZE_DEPS \
 && docker-php-ext-install pdo_mysql opcache intl mbstring \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del .build

COPY deploy/docker/php.ini       /usr/local/etc/php/conf.d/zz-app.ini
COPY deploy/docker/php-fpm.conf  /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /var/www/html
COPY --from=deps --chown=www-data:www-data /app /var/www/html
USER www-data
EXPOSE 9000
HEALTHCHECK --interval=10s --timeout=2s CMD php -r "exit(extension_loaded('opcache')?0:1);"
```

- [ ] **Step 3: Write `deploy/docker/php.ini`**

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /proc/self/fd/2
memory_limit = 256M

opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.preload = /var/www/html/deploy/docker/preload.php
opcache.preload_user = www-data

opcache.jit_buffer_size = 128M
opcache.jit = tracing

session.cookie_httponly = 1
session.cookie_samesite = "Lax"
session.use_strict_mode = 1
```

- [ ] **Step 4: Write `deploy/docker/php-fpm.conf`**

```ini
[www]
listen = 9000
listen.allowed_clients = any
pm = dynamic
pm.max_children = 16
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
clear_env = no
```

- [ ] **Step 5: Write `deploy/docker/preload.php`**

```php
<?php

declare(strict_types=1);

opcache_compile_file(__DIR__ . '/../../vendor/autoload.php');

$files = glob(__DIR__ . '/../../src/{,**/}*.php', GLOB_BRACE) ?: [];
foreach ($files as $f) {
    opcache_compile_file($f);
}
```

- [ ] **Step 6: Write `deploy/docker/nginx.conf`**

```nginx
server {
    listen 80 default_server;
    server_name _;
    root /var/www/html/public;
    index index.php;
    client_max_body_size 4m;

    location = /healthz { try_files /healthz @app; }
    location / { try_files $uri /index.php?$query_string; }
    location @app {
        fastcgi_pass app:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    }
    location ~ \.php$ {
        fastcgi_pass app:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    location ~* \.(?:css|js|jpg|png|svg|ico)$ {
        expires 7d;
        access_log off;
    }
}
```

- [ ] **Step 7: Verify image builds**

Run: `docker build -t phpenterpriseblog:dev -f deploy/docker/Dockerfile .`
Expected: image builds. Confirm: `docker images phpenterpriseblog:dev`.

- [ ] **Step 8: Commit**

```bash
git add .dockerignore deploy/docker/
git commit -m "docker: add multi-stage Dockerfile, php.ini (opcache + JIT), nginx.conf

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 4.2: docker-compose.yml + Makefile

**Files:**
- Create: `docker-compose.yml`
- Create: `Makefile`

- [ ] **Step 1: Write `docker-compose.yml`**

```yaml
services:
  app:
    build:
      context: .
      dockerfile: deploy/docker/Dockerfile
      target: runtime
    volumes:
      - .:/var/www/html
    environment:
      APP_ENV: local
      APP_DEBUG: "true"
      DB_HOST: mysql
      DB_PORT: "3306"
      DB_DATABASE: phpenterpriseblog
      DB_USERNAME: root
      DB_PASSWORD: root
      REDIS_HOST: redis
      REDIS_PORT: "6379"
      REDIS_PASSWORD: devpass
      REDIS_DSN: redis://:devpass@redis:6379/0
      CACHE_DRIVER: redis
      SESSION_DRIVER: redis
    depends_on:
      mysql:  { condition: service_healthy }
      redis:  { condition: service_healthy }

  nginx:
    image: nginx:1.27-alpine
    ports: ["8080:80"]
    volumes:
      - ./deploy/docker/nginx.conf:/etc/nginx/conf.d/default.conf:ro
      - .:/var/www/html:ro
    depends_on: [app]

  mysql:
    image: mysql:8.4
    environment:
      MYSQL_DATABASE: phpenterpriseblog
      MYSQL_ROOT_PASSWORD: root
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-uroot", "-proot"]
      interval: 5s
      retries: 10
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    command: ["redis-server", "--requirepass", "devpass"]
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "devpass", "ping"]
      interval: 5s
      retries: 10

  mailhog:
    image: mailhog/mailhog
    ports: ["8025:8025"]

volumes:
  mysql_data: {}
```

- [ ] **Step 2: Write `Makefile`**

```makefile
.PHONY: up down sh test test-all ci stan cs cs-fix migrate migrate-fresh e2e logs

up:
	docker compose up -d --wait
	$(MAKE) migrate

down:
	docker compose down -v

sh:
	docker compose exec app sh

logs:
	docker compose logs -f --tail=100

test:
	docker compose exec -T app composer test

test-all:
	docker compose exec -T app composer test:all

ci:
	docker compose exec -T app composer ci

stan:
	docker compose exec -T app composer stan

cs:
	docker compose exec -T app composer cs

cs-fix:
	docker compose exec -T app composer cs:fix

migrate:
	docker compose exec -T app php bin/console migrate

migrate-fresh:
	docker compose exec -T app php bin/console migrate --fresh

e2e:
	cd tests/E2e && npx playwright test
```

- [ ] **Step 3: Verify**

Run: `docker compose config --quiet`
Expected: exit 0.

- [ ] **Step 4: Commit**

```bash
git add docker-compose.yml Makefile
git commit -m "compose: add local dev stack (php-fpm, nginx, mysql, redis, mailhog) + Makefile

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 4.3: `/healthz` endpoint

**Files:**
- Create: `src/Controllers/HealthController.php`
- Modify: `config/routes.php` — uncomment the `healthz` line from Task 1.12.

**Interfaces:**
- Consumes: `PDO`, Redis (optional via env).
- Produces: `GET /healthz` returns 200 + `{"db":"ok","redis":"ok|skipped","version":"<sha>"}`.

- [ ] **Step 1: Write `src/Controllers/HealthController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Env;
use App\Support\View;
use PDO;
use Redis;
use Throwable;

final class HealthController extends BaseController
{
    public function __construct(View $view, private readonly PDO $pdo)
    {
        parent::__construct($view);
    }

    public function check(): void
    {
        $db = $this->probe(fn() => $this->pdo->query('SELECT 1')?->fetchColumn() === '1');

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

        header('Content-Type: application/json');
        echo json_encode([
            'db'      => $db,
            'redis'   => $redis,
            'version' => Env::string('APP_VERSION', 'dev'),
        ]);
        exit;
    }

    private function probe(callable $fn): string
    {
        try {
            return $fn() ? 'ok' : 'fail';
        } catch (Throwable) {
            return 'fail';
        }
    }
}
```

- [ ] **Step 2: Uncomment the route**

Edit `config/routes.php` so the `'healthz' => [App\Controllers\HealthController::class, 'check']` line is active under `GET`.

- [ ] **Step 3: Container wiring**

In `src/Kernel.php`, inside `buildContainer()`, add: `$container->add(HealthController::class)->addArguments([View::class, PDO::class]);`

- [ ] **Step 4: Bring it up and curl**

Run: `make up`
Then: `curl -s localhost:8080/healthz`
Expected: `{"db":"ok","redis":"ok","version":"dev"}`.

- [ ] **Step 5: Commit**

```bash
git add src/Controllers/HealthController.php config/routes.php src/Kernel.php
git commit -m "health: add /healthz endpoint (db + redis probes)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Phase 5 — Caching

### Task 5.1: PSR-16 RedisCache + container wiring

**Files:**
- Create: `src/Cache/CacheInterface.php`
- Create: `src/Cache/RedisCache.php`

**Interfaces:**
- Produces: `App\Cache\CacheInterface extends Psr\SimpleCache\CacheInterface`. `App\Cache\RedisCache::__construct(string $dsn, string $prefix = 'fb:')` wrapping `symfony/cache` RedisAdapter.

- [ ] **Step 1: Write `src/Cache/CacheInterface.php`**

```php
<?php

declare(strict_types=1);

namespace App\Cache;

use Psr\SimpleCache\CacheInterface as Psr16;

interface CacheInterface extends Psr16
{
}
```

- [ ] **Step 2: Write `src/Cache/RedisCache.php`**

```php
<?php

declare(strict_types=1);

namespace App\Cache;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class RedisCache implements CacheInterface
{
    private readonly Psr16Cache $inner;

    public function __construct(string $dsn, string $namespace = 'fb')
    {
        $client = RedisAdapter::createConnection($dsn);
        $adapter = new RedisAdapter($client, $namespace);
        $this->inner = new Psr16Cache($adapter);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->inner->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->inner->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->inner->delete($key);
    }

    public function clear(): bool
    {
        return $this->inner->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->inner->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        return $this->inner->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->inner->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->inner->has($key);
    }
}
```

- [ ] **Step 3: Wire in Kernel**

In `src/Kernel.php::buildContainer()`, add:
```php
$container->addShared(CacheInterface::class, fn() => new RedisCache(Env::string('REDIS_DSN')));
```

- [ ] **Step 4: Commit**

```bash
git add src/Cache/ src/Kernel.php
git commit -m "cache: add PSR-16 RedisCache backed by symfony/cache

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 5.2: CachedPostRepository decorator + tests

**Files:**
- Create: `src/Cache/CachedPostRepository.php`
- Create: `tests/Unit/Cache/CachedPostRepositoryTest.php`

**Interfaces:**
- Produces: `App\Cache\CachedPostRepository extends PostRepository` (so consumers don't care). `all()` cache key `posts:list:v1` TTL 60. `findById($id)` cache key `posts:show:{id}:v1` TTL 300. Writes invalidate both keys.

- [ ] **Step 1: Make PostRepository methods non-final and class non-final**

(Already the case from Task 1.5 — `class PostRepository` without `final`, methods without `final`.)

- [ ] **Step 2: Write `src/Cache/CachedPostRepository.php`**

```php
<?php

declare(strict_types=1);

namespace App\Cache;

use App\Models\Post;
use App\Repositories\PostRepository;
use PDO;

final class CachedPostRepository extends PostRepository
{
    private const TTL_LIST = 60;
    private const TTL_SHOW = 300;
    private const KEY_LIST = 'posts:list:v1';

    public function __construct(PDO $pdo, private readonly CacheInterface $cache)
    {
        parent::__construct($pdo);
    }

    public function all(): array
    {
        $cached = $this->cache->get(self::KEY_LIST);
        if (is_array($cached)) {
            return $cached;
        }
        $fresh = parent::all();
        $this->cache->set(self::KEY_LIST, $fresh, self::TTL_LIST);
        return $fresh;
    }

    public function findById(int $id): ?Post
    {
        $key = self::keyShow($id);
        $cached = $this->cache->get($key);
        if ($cached instanceof Post) {
            return $cached;
        }
        $fresh = parent::findById($id);
        if ($fresh !== null) {
            $this->cache->set($key, $fresh, self::TTL_SHOW);
        }
        return $fresh;
    }

    public function save(array $data): int
    {
        $id = parent::save($data);
        $this->invalidate($id);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $ok = parent::update($id, $data);
        $this->invalidate($id);
        return $ok;
    }

    public function delete(int $id): bool
    {
        $ok = parent::delete($id);
        $this->invalidate($id);
        return $ok;
    }

    private function invalidate(int $id): void
    {
        $this->cache->delete(self::KEY_LIST);
        $this->cache->delete(self::keyShow($id));
    }

    private static function keyShow(int $id): string
    {
        return "posts:show:{$id}:v1";
    }
}
```

- [ ] **Step 3: Write the unit test using an in-memory cache double**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Cache\CacheInterface;
use App\Cache\CachedPostRepository;
use App\Models\Post;
use App\Repositories\PostRepository;
use PHPUnit\Framework\TestCase;

final class CachedPostRepositoryTest extends TestCase
{
    private function fakeCache(): CacheInterface
    {
        return new class implements CacheInterface {
            public array $store = [];
            public function get(string $key, mixed $default = null): mixed { return $this->store[$key] ?? $default; }
            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool { $this->store[$key] = $value; return true; }
            public function delete(string $key): bool { unset($this->store[$key]); return true; }
            public function clear(): bool { $this->store = []; return true; }
            public function getMultiple(iterable $keys, mixed $default = null): iterable { foreach ($keys as $k) yield $k => $this->get($k, $default); }
            public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool { foreach ($values as $k=>$v) $this->set($k,$v,$ttl); return true; }
            public function deleteMultiple(iterable $keys): bool { foreach ($keys as $k) $this->delete($k); return true; }
            public function has(string $key): bool { return isset($this->store[$key]); }
        };
    }

    public function testInvalidationOnSaveClearsListKey(): void
    {
        $cache = $this->fakeCache();
        $cache->set('posts:list:v1', ['stale']);

        $inner = $this->createMock(PostRepository::class);
        $inner->expects($this->never())->method('all');  // we call save, not all()
        $inner->method('save')->willReturn(42);

        // Build a decorator that delegates parent::save by extending and overriding only what we need
        $sut = new class($inner, $cache) extends CachedPostRepository {
            public function __construct(private PostRepository $real, CacheInterface $cache) {
                parent::__construct(new \PDO('sqlite::memory:'), $cache);
            }
            public function save(array $data): int { $id = $this->real->save($data); $this->invalidatePublic($id); return $id; }
            public function invalidatePublic(int $id): void {
                $r = new \ReflectionClass(CachedPostRepository::class);
                $m = $r->getMethod('invalidate'); $m->invoke($this, $id);
            }
        };

        $sut->save(['user_id' => 1, 'title' => 't', 'message' => 'm']);
        self::assertNull($cache->get('posts:list:v1'));
    }
}
```

> Note: this test exercises the invalidation contract. A full hit/miss round-trip is covered in the integration suite (Task 5.4).

- [ ] **Step 4: Run**

Run: `composer test`
Expected: all unit tests pass.

- [ ] **Step 5: Commit**

```bash
git add src/Cache/CachedPostRepository.php tests/Unit/Cache/
git commit -m "cache(posts): add decorator + invalidation contract test

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 5.3: Redis session handler (production)

**Files:**
- Create: `src/Session/RedisSessionHandler.php`
- Modify: `src/Kernel.php` — register handler when `SESSION_DRIVER=redis-tls`.

**Interfaces:**
- Produces: `App\Session\RedisSessionHandler implements SessionHandlerInterface`.

- [ ] **Step 1: Write `src/Session/RedisSessionHandler.php`**

```php
<?php

declare(strict_types=1);

namespace App\Session;

use Redis;
use SessionHandlerInterface;

final class RedisSessionHandler implements SessionHandlerInterface
{
    public function __construct(
        private readonly Redis $redis,
        private readonly int $ttl = 7200,
        private readonly string $prefix = 'sess:',
    ) {
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string
    {
        $v = $this->redis->get($this->prefix . $id);
        return is_string($v) ? $v : '';
    }

    public function write(string $id, string $data): bool
    {
        return $this->redis->setex($this->prefix . $id, $this->ttl, $data) === true;
    }

    public function destroy(string $id): bool
    {
        $this->redis->del($this->prefix . $id);
        return true;
    }

    public function gc(int $maxLifetime): int|false
    {
        return 0; // Redis TTL handles expiry
    }
}
```

- [ ] **Step 2: Wire into Kernel**

In `Kernel::handle()` *before* `session_start()`:

```php
if (Env::string('SESSION_DRIVER', 'files') === 'redis-tls') {
    $redis = new \Redis();
    $redis->connect('tls://' . Env::string('REDIS_HOST'), Env::int('REDIS_PORT'));
    $pw = Env::string('REDIS_PASSWORD', '');
    if ($pw !== '') { $redis->auth($pw); }
    session_set_save_handler(new \App\Session\RedisSessionHandler($redis), true);
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Session/ src/Kernel.php
git commit -m "session: add RedisSessionHandler for ElastiCache TLS path

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 5.4: Integration suite — repositories + cache + session

**Files:**
- Create: `tests/Integration/IntegrationTestCase.php`
- Create: `tests/Integration/Repositories/PostRepositoryIntegrationTest.php`
- Create: `tests/Integration/Cache/CachedPostRepositoryIntegrationTest.php`
- Create: `tests/Integration/Session/RedisSessionHandlerIntegrationTest.php`

**Interfaces:**
- Consumes: live MySQL + Redis (via env: `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1` in CI).
- Produces: integration suite runnable via `composer test:int`.

- [ ] **Step 1: Write `tests/Integration/IntegrationTestCase.php`**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\ConnectionFactory;
use App\Support\Env;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = ConnectionFactory::fromEnv();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['post_comments', 'posts', 'users', 'migrations'] as $t) {
            $this->pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        $sql = file_get_contents(dirname(__DIR__, 2) . '/database/migrations/0001_init.sql');
        $this->pdo->exec($sql);
    }

    protected function seedUser(string $email = 'u@e.co'): int
    {
        $this->pdo->prepare('INSERT INTO users (username, email, password, roletype) VALUES (?,?,?,?)')
            ->execute(['user', $email, password_hash('secret123', PASSWORD_DEFAULT), 'user']);
        return (int) $this->pdo->lastInsertId();
    }
}
```

- [ ] **Step 2: Write `PostRepositoryIntegrationTest.php`**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\PostRepository;
use Tests\Integration\IntegrationTestCase;

final class PostRepositoryIntegrationTest extends IntegrationTestCase
{
    public function testFullCrudRoundTrip(): void
    {
        $uid  = $this->seedUser();
        $repo = new PostRepository($this->pdo);

        $id = $repo->save(['user_id' => $uid, 'title' => 't1', 'message' => 'body1']);
        self::assertGreaterThan(0, $id);

        $found = $repo->findById($id);
        self::assertNotNull($found);
        self::assertSame('body1', $found->message);   // regression: not the email field

        $repo->update($id, ['title' => 't2', 'message' => 'body2']);
        self::assertSame('body2', $repo->findById($id)?->message);

        $all = $repo->all();
        self::assertCount(1, $all);

        $repo->delete($id);
        self::assertNull($repo->findById($id));
    }
}
```

- [ ] **Step 3: Write `CachedPostRepositoryIntegrationTest.php`**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Cache;

use App\Cache\CachedPostRepository;
use App\Cache\RedisCache;
use App\Support\Env;
use Tests\Integration\IntegrationTestCase;

final class CachedPostRepositoryIntegrationTest extends IntegrationTestCase
{
    public function testHitMissAndInvalidate(): void
    {
        $uid   = $this->seedUser();
        $cache = new RedisCache(Env::string('REDIS_DSN'), 'fb-test-' . bin2hex(random_bytes(4)));
        $cache->clear();

        $repo = new CachedPostRepository($this->pdo, $cache);
        $id   = $repo->save(['user_id' => $uid, 'title' => 't', 'message' => 'm']);

        self::assertNull($cache->get('posts:list:v1'), 'save() invalidates list');
        $first  = $repo->all();
        $cached = $cache->get('posts:list:v1');
        self::assertNotNull($cached, 'all() populates cache');
        self::assertEquals($first, $cached);

        $repo->update($id, ['title' => 'new', 'message' => 'm2']);
        self::assertNull($cache->get('posts:list:v1'), 'update() invalidates list');
    }
}
```

- [ ] **Step 4: Write `RedisSessionHandlerIntegrationTest.php`**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Session;

use App\Session\RedisSessionHandler;
use App\Support\Env;
use PHPUnit\Framework\TestCase;
use Redis;

final class RedisSessionHandlerIntegrationTest extends TestCase
{
    public function testWriteReadRoundTrip(): void
    {
        $redis = new Redis();
        $redis->connect(Env::string('REDIS_HOST'), Env::int('REDIS_PORT', 6379));
        $pw = Env::string('REDIS_PASSWORD', '');
        if ($pw !== '') {
            $redis->auth($pw);
        }
        $h = new RedisSessionHandler($redis, ttl: 60, prefix: 'sess-test:');
        self::assertTrue($h->write('abc', 'payload'));
        self::assertSame('payload', $h->read('abc'));
        self::assertTrue($h->destroy('abc'));
        self::assertSame('', $h->read('abc'));
    }
}
```

- [ ] **Step 5: Run the integration suite locally**

Run: `make up && docker compose exec -T -e DB_HOST=mysql -e REDIS_HOST=redis app composer test:int`
Expected: all integration tests pass.

- [ ] **Step 6: Commit**

```bash
git add tests/Integration/
git commit -m "test(int): repositories, cache decorator, redis session round-trips

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Phase 6 — Continuous Integration

### Task 6.1: `ci.yml` — quality matrix + integration

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Write `.github/workflows/ci.yml`**

```yaml
name: ci
on:
  pull_request: {}
  push:
    branches: [main]
concurrency:
  group: ci-${{ github.ref }}
  cancel-in-progress: true

jobs:
  quality:
    runs-on: ubuntu-24.04
    strategy:
      fail-fast: false
      matrix:
        php: ["8.4", "8.5"]
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: pdo_mysql, redis, intl, mbstring
          coverage: none
      - uses: ramsey/composer-install@v3
      - run: composer cs
      - run: composer stan
      - run: composer test

  integration:
    runs-on: ubuntu-24.04
    services:
      mysql:
        image: mysql:8.4
        env:
          MYSQL_DATABASE: phpenterpriseblog
          MYSQL_ROOT_PASSWORD: root
        ports: ["3306:3306"]
        options: >-
          --health-cmd="mysqladmin ping -uroot -proot"
          --health-interval=5s --health-retries=10
      redis:
        image: redis:7-alpine
        ports: ["6379:6379"]
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=5s --health-retries=10
    env:
      DB_HOST: 127.0.0.1
      DB_PORT: 3306
      DB_DATABASE: phpenterpriseblog
      DB_USERNAME: root
      DB_PASSWORD: root
      REDIS_HOST: 127.0.0.1
      REDIS_PORT: 6379
      REDIS_PASSWORD: ""
      REDIS_DSN: redis://127.0.0.1:6379/0
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.5"
          extensions: pdo_mysql, redis, intl, mbstring
      - uses: ramsey/composer-install@v3
      - run: php bin/console migrate
      - run: composer test:int

  e2e:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@v4
      - run: docker compose up -d --wait
      - run: docker compose exec -T app php bin/console migrate
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: |
          cd tests/E2e
          npm ci || npm install
          npx playwright install --with-deps chromium
          npx playwright test
      - if: always()
        uses: actions/upload-artifact@v4
        with:
          name: playwright-report
          path: tests/E2e/playwright-report
          if-no-files-found: ignore
```

- [ ] **Step 2: Validate the YAML locally**

Run: `yq eval '.jobs | keys' .github/workflows/ci.yml`
Expected: lists `quality`, `integration`, `e2e`. (Alternative: `actionlint .github/workflows/ci.yml` if installed.)

If a GitHub remote exists, push to a branch and watch the workflow: `git push -u origin HEAD`. Expect `quality` and `integration` green; `e2e` may red until Phase 8 specs land — that's fine for now.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: add GitHub Actions workflow (quality matrix, integration, e2e)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Phase 7 — Continuous Delivery (EKS via Helm)

### Task 7.1: `deploy/aws/README.md` — one-time prereqs

**Files:**
- Create: `deploy/aws/README.md`

- [ ] **Step 1: Write the doc**

```markdown
# AWS prerequisites (one-time)

These resources are set up **once** outside of CI. Steps assume `aws` CLI is configured for the target account.

## 1. ECR repository
\`\`\`bash
aws ecr create-repository --repository-name phpenterpriseblog --image-scanning-configuration scanOnPush=true
\`\`\`

## 2. GitHub OIDC provider
If not already present:
\`\`\`bash
aws iam create-open-id-connect-provider \\
  --url https://token.actions.githubusercontent.com \\
  --client-id-list sts.amazonaws.com \\
  --thumbprint-list 6938fd4d98bab03faadb97b34396831e3780aea1
\`\`\`

## 3. IAM roles (OIDC-assumable)

### `phpenterpriseblog-ci-ecr`
Trust policy restricts to `repo:<OWNER>/phpenterpriseblog:ref:refs/heads/main` and `repo:<OWNER>/phpenterpriseblog:ref:refs/tags/v*`.
Permissions: `ecr:GetAuthorizationToken`, `ecr:BatchCheckLayerAvailability`, `ecr:PutImage`, `ecr:InitiateLayerUpload`, `ecr:UploadLayerPart`, `ecr:CompleteLayerUpload`, scoped to the `phpenterpriseblog` repository ARN only.

### `phpenterpriseblog-cd-eks`
Trust policy restricts to `repo:<OWNER>/phpenterpriseblog:ref:refs/tags/v*`.
Permissions: `eks:DescribeCluster`, `secretsmanager:GetSecretValue` on `arn:aws:secretsmanager:*:*:secret:phpenterpriseblog/*`.

## 4. EKS cluster
Any EKS ≥ 1.30 cluster. Create namespaces:
\`\`\`bash
kubectl create namespace phpenterpriseblog-staging
kubectl create namespace phpenterpriseblog-prod
\`\`\`

## 5. RDS MySQL 8
One instance per environment. Store credentials in AWS Secrets Manager:
- `phpenterpriseblog/staging/db` → JSON: `{ "DB_HOST", "DB_PORT", "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD" }`
- `phpenterpriseblog/prod/db`

## 6. ElastiCache Redis (TLS)
One cluster per env. Secret:
- `phpenterpriseblog/{env}/redis` → JSON: `{ "REDIS_HOST", "REDIS_PORT", "REDIS_PASSWORD" }`

## 7. Cluster add-ons
- AWS Load Balancer Controller
- External Secrets Operator (configured with the IRSA-bound `ClusterSecretStore` pointing to AWS Secrets Manager)
- (optional) Metrics Server for HPA

## 8. GitHub repo variables
Set as `Settings → Secrets and variables → Actions → Variables`:
- `AWS_ACCOUNT` — the account id
- `AWS_REGION` — e.g. `us-east-1`
- `ECR_REGISTRY` — `<account>.dkr.ecr.<region>.amazonaws.com`
- `EKS_CLUSTER` — cluster name

## 9. GitHub Environments
Create:
- `staging` — no required reviewers
- `production` — required reviewers ON
```

- [ ] **Step 2: Commit**

```bash
git add deploy/aws/README.md
git commit -m "docs(aws): one-time AWS prereqs for OIDC, ECR, EKS, RDS, ElastiCache

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 7.2: Helm chart skeleton + values

**Files:**
- Create: `deploy/helm/phpenterpriseblog/Chart.yaml`
- Create: `deploy/helm/phpenterpriseblog/values.yaml`
- Create: `deploy/helm/phpenterpriseblog/values.staging.yaml`
- Create: `deploy/helm/phpenterpriseblog/values.prod.yaml`
- Create: `deploy/helm/phpenterpriseblog/.helmignore`

- [ ] **Step 1: Write `Chart.yaml`**

```yaml
apiVersion: v2
name: phpenterpriseblog
description: PHP 8.5 enterprise-shaped blog
type: application
version: 0.1.0
appVersion: "0.1.0"
```

- [ ] **Step 2: Write `values.yaml`**

```yaml
image:
  repository: ""
  tag: ""
  pullPolicy: IfNotPresent

replicaCount: 2

resources:
  php:
    requests: { cpu: 100m, memory: 256Mi }
    limits:   { cpu: 1,    memory: 512Mi }
  nginx:
    requests: { cpu: 20m,  memory: 32Mi  }
    limits:   { cpu: 200m, memory: 64Mi  }

service:
  port: 80

ingress:
  enabled: true
  className: alb
  hosts: []
  annotations: {}
  tls: []

probes:
  liveness:
    httpGet: { path: /healthz, port: http }
    periodSeconds: 10
  readiness:
    httpGet: { path: /healthz, port: http }
    periodSeconds: 5

hpa:
  enabled: true
  minReplicas: 2
  maxReplicas: 10
  targetCPUUtilizationPercentage: 70

pdb:
  enabled: true
  minAvailable: 1

externalSecrets:
  enabled: true
  refreshInterval: 1h
  secretStoreRef:
    kind: ClusterSecretStore
    name: aws-secretsmanager
  secrets:
    - name: phpenterpriseblog-db
      remoteRef: phpenterpriseblog/staging/db
    - name: phpenterpriseblog-redis
      remoteRef: phpenterpriseblog/staging/redis

env:
  APP_ENV: production
  APP_DEBUG: "false"
  CACHE_DRIVER: redis
  SESSION_DRIVER: redis-tls
  LOG_CHANNEL: stderr
  LOG_LEVEL: info

podSecurityContext:
  runAsNonRoot: true
  runAsUser: 33
  fsGroup: 33
  seccompProfile: { type: RuntimeDefault }

containerSecurityContext:
  allowPrivilegeEscalation: false
  readOnlyRootFilesystem: true
  capabilities: { drop: [ALL] }

serviceAccount:
  create: true
  name: phpenterpriseblog
  annotations: {}   # set IRSA role ARN at install time via values.<env>.yaml
```

- [ ] **Step 3: Write `values.staging.yaml`**

```yaml
image:
  repository: "${ECR_REGISTRY}/phpenterpriseblog"  # overridden by --set in CI
env:
  APP_ENV: staging
ingress:
  hosts:
    - { host: staging.phpenterpriseblog.example.com, paths: [ { path: /, pathType: Prefix } ] }
externalSecrets:
  secrets:
    - { name: phpenterpriseblog-db,    remoteRef: phpenterpriseblog/staging/db }
    - { name: phpenterpriseblog-redis, remoteRef: phpenterpriseblog/staging/redis }
```

- [ ] **Step 4: Write `values.prod.yaml`**

```yaml
image:
  repository: "${ECR_REGISTRY}/phpenterpriseblog"
env:
  APP_ENV: production
ingress:
  hosts:
    - { host: phpenterpriseblog.example.com, paths: [ { path: /, pathType: Prefix } ] }
externalSecrets:
  secrets:
    - { name: phpenterpriseblog-db,    remoteRef: phpenterpriseblog/prod/db }
    - { name: phpenterpriseblog-redis, remoteRef: phpenterpriseblog/prod/redis }
hpa: { minReplicas: 3, maxReplicas: 20 }
```

- [ ] **Step 5: Commit**

```bash
git add deploy/helm/phpenterpriseblog/Chart.yaml deploy/helm/phpenterpriseblog/values*.yaml
git commit -m "helm: add chart skeleton and per-env values

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 7.3: Helm templates — workload

**Files:**
- Create: `deploy/helm/phpenterpriseblog/templates/_helpers.tpl`
- Create: `deploy/helm/phpenterpriseblog/templates/serviceaccount.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/deployment.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/service.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/ingress.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/configmap-nginx.yaml`

- [ ] **Step 1: Write `_helpers.tpl`**

```yaml
{{- define "fb.name" -}}phpenterpriseblog{{- end -}}
{{- define "fb.labels" -}}
app.kubernetes.io/name: {{ include "fb.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end -}}
```

- [ ] **Step 2: Write `serviceaccount.yaml`**

```yaml
{{- if .Values.serviceAccount.create }}
apiVersion: v1
kind: ServiceAccount
metadata:
  name: {{ .Values.serviceAccount.name }}
  labels: { {{ include "fb.labels" . | nindent 4 }} }
  annotations:
    {{- toYaml .Values.serviceAccount.annotations | nindent 4 }}
{{- end }}
```

- [ ] **Step 3: Write `configmap-nginx.yaml`**

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: {{ include "fb.name" . }}-nginx
data:
  default.conf: |-
{{ .Files.Get "files/nginx.conf" | indent 4 }}
```

Add the file at `deploy/helm/phpenterpriseblog/files/nginx.conf` — a copy of `deploy/docker/nginx.conf` but with `fastcgi_pass 127.0.0.1:9000;` (single-pod, two-container).

- [ ] **Step 4: Write `deployment.yaml`**

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: {{ include "fb.name" . }}
  labels: { {{ include "fb.labels" . | nindent 4 }} }
spec:
  replicas: {{ .Values.replicaCount }}
  selector:
    matchLabels: { app.kubernetes.io/name: {{ include "fb.name" . }} }
  template:
    metadata:
      labels: { app.kubernetes.io/name: {{ include "fb.name" . }} }
    spec:
      serviceAccountName: {{ .Values.serviceAccount.name }}
      securityContext: {{- toYaml .Values.podSecurityContext | nindent 8 }}
      volumes:
        - name: nginx-conf
          configMap: { name: {{ include "fb.name" . }}-nginx }
        - name: tmp
          emptyDir: {}
      containers:
        - name: php
          image: "{{ .Values.image.repository }}:{{ .Values.image.tag }}"
          imagePullPolicy: {{ .Values.image.pullPolicy }}
          securityContext: {{- toYaml .Values.containerSecurityContext | nindent 12 }}
          ports: [ { name: fpm, containerPort: 9000 } ]
          envFrom:
            - secretRef: { name: phpenterpriseblog-db }
            - secretRef: { name: phpenterpriseblog-redis }
          env:
            {{- range $k, $v := .Values.env }}
            - { name: {{ $k }}, value: "{{ $v }}" }
            {{- end }}
          volumeMounts:
            - { name: tmp, mountPath: /tmp }
          resources: {{- toYaml .Values.resources.php | nindent 12 }}
        - name: nginx
          image: nginx:1.27-alpine
          securityContext: {{- toYaml .Values.containerSecurityContext | nindent 12 }}
          ports: [ { name: http, containerPort: 80 } ]
          volumeMounts:
            - { name: nginx-conf, mountPath: /etc/nginx/conf.d/default.conf, subPath: default.conf, readOnly: true }
            - { name: tmp,        mountPath: /var/cache/nginx }
            - { name: tmp,        mountPath: /var/run }
          livenessProbe:  {{- toYaml .Values.probes.liveness  | nindent 12 }}
          readinessProbe: {{- toYaml .Values.probes.readiness | nindent 12 }}
          resources: {{- toYaml .Values.resources.nginx | nindent 12 }}
```

- [ ] **Step 5: Write `service.yaml`**

```yaml
apiVersion: v1
kind: Service
metadata: { name: {{ include "fb.name" . }} }
spec:
  selector: { app.kubernetes.io/name: {{ include "fb.name" . }} }
  ports: [ { name: http, port: {{ .Values.service.port }}, targetPort: http } ]
```

- [ ] **Step 6: Write `ingress.yaml`**

```yaml
{{- if .Values.ingress.enabled }}
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: {{ include "fb.name" . }}
  annotations:
    {{- toYaml .Values.ingress.annotations | nindent 4 }}
spec:
  ingressClassName: {{ .Values.ingress.className }}
  rules:
    {{- range .Values.ingress.hosts }}
    - host: {{ .host }}
      http:
        paths:
          {{- range .paths }}
          - path: {{ .path }}
            pathType: {{ .pathType }}
            backend:
              service:
                name: {{ include "fb.name" $ }}
                port: { number: {{ $.Values.service.port }} }
          {{- end }}
    {{- end }}
{{- end }}
```

- [ ] **Step 7: Render dry-run**

Run: `helm template phpenterpriseblog deploy/helm/phpenterpriseblog -f deploy/helm/phpenterpriseblog/values.staging.yaml --set image.tag=v0.1.0 --set image.repository=stub/phpenterpriseblog | head -80`
Expected: valid YAML rendered.

- [ ] **Step 8: Commit**

```bash
git add deploy/helm/phpenterpriseblog/
git commit -m "helm: deployment + service + ingress + nginx configmap + SA

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 7.4: Helm templates — HPA, PDB, ExternalSecret, Job, NetworkPolicy

**Files:**
- Create: `deploy/helm/phpenterpriseblog/templates/hpa.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/pdb.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/externalsecret.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/job-migrate.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/networkpolicy.yaml`
- Create: `deploy/helm/phpenterpriseblog/templates/tests/test-healthz.yaml`

- [ ] **Step 1: Write `hpa.yaml`**

```yaml
{{- if .Values.hpa.enabled }}
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata: { name: {{ include "fb.name" . }} }
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: {{ include "fb.name" . }}
  minReplicas: {{ .Values.hpa.minReplicas }}
  maxReplicas: {{ .Values.hpa.maxReplicas }}
  metrics:
    - type: Resource
      resource:
        name: cpu
        target: { type: Utilization, averageUtilization: {{ .Values.hpa.targetCPUUtilizationPercentage }} }
{{- end }}
```

- [ ] **Step 2: Write `pdb.yaml`**

```yaml
{{- if .Values.pdb.enabled }}
apiVersion: policy/v1
kind: PodDisruptionBudget
metadata: { name: {{ include "fb.name" . }} }
spec:
  minAvailable: {{ .Values.pdb.minAvailable }}
  selector: { matchLabels: { app.kubernetes.io/name: {{ include "fb.name" . }} } }
{{- end }}
```

- [ ] **Step 3: Write `externalsecret.yaml`**

```yaml
{{- range .Values.externalSecrets.secrets }}
apiVersion: external-secrets.io/v1beta1
kind: ExternalSecret
metadata:
  name: {{ .name }}
spec:
  refreshInterval: {{ $.Values.externalSecrets.refreshInterval }}
  secretStoreRef:
    kind: {{ $.Values.externalSecrets.secretStoreRef.kind }}
    name: {{ $.Values.externalSecrets.secretStoreRef.name }}
  target: { name: {{ .name }}, creationPolicy: Owner }
  dataFrom:
    - extract: { key: {{ .remoteRef }} }
---
{{- end }}
```

- [ ] **Step 4: Write `job-migrate.yaml`**

```yaml
apiVersion: batch/v1
kind: Job
metadata:
  name: {{ include "fb.name" . }}-migrate-{{ .Release.Revision }}
  annotations:
    "helm.sh/hook": pre-install,pre-upgrade
    "helm.sh/hook-weight": "-5"
    "helm.sh/hook-delete-policy": before-hook-creation,hook-succeeded
spec:
  backoffLimit: 1
  template:
    spec:
      restartPolicy: Never
      serviceAccountName: {{ .Values.serviceAccount.name }}
      securityContext: {{- toYaml .Values.podSecurityContext | nindent 8 }}
      containers:
        - name: migrate
          image: "{{ .Values.image.repository }}:{{ .Values.image.tag }}"
          command: ["php", "bin/console", "migrate"]
          securityContext: {{- toYaml .Values.containerSecurityContext | nindent 12 }}
          envFrom:
            - secretRef: { name: phpenterpriseblog-db }
          env:
            {{- range $k, $v := .Values.env }}
            - { name: {{ $k }}, value: "{{ $v }}" }
            {{- end }}
```

- [ ] **Step 5: Write `networkpolicy.yaml`**

```yaml
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata: { name: {{ include "fb.name" . }} }
spec:
  podSelector: { matchLabels: { app.kubernetes.io/name: {{ include "fb.name" . }} } }
  policyTypes: [Ingress, Egress]
  ingress:
    - from: [{ namespaceSelector: {} }]
      ports: [{ port: 80 }]
  egress:
    - to:   [{ namespaceSelector: {} }]
      ports: [{ port: 53, protocol: UDP }, { port: 53, protocol: TCP }]
    - ports: [{ port: 3306 }, { port: 6379 }, { port: 443 }]
```

- [ ] **Step 6: Write `tests/test-healthz.yaml`**

```yaml
apiVersion: v1
kind: Pod
metadata:
  name: "{{ include "fb.name" . }}-test-healthz"
  annotations: { "helm.sh/hook": test }
spec:
  restartPolicy: Never
  containers:
    - name: curl
      image: curlimages/curl:8.10.1
      command: ["sh", "-c", "curl -fsS http://{{ include "fb.name" . }}/healthz | grep '\"db\":\"ok\"'"]
```

- [ ] **Step 7: Lint + render**

Run: `helm lint deploy/helm/phpenterpriseblog && helm template deploy/helm/phpenterpriseblog -f deploy/helm/phpenterpriseblog/values.prod.yaml --set image.tag=v0.1.0 --set image.repository=stub > /tmp/render.yaml && wc -l /tmp/render.yaml`
Expected: `helm lint` clean; render produces non-empty multi-doc YAML.

- [ ] **Step 8: Commit**

```bash
git add deploy/helm/phpenterpriseblog/templates/
git commit -m "helm: HPA, PDB, ExternalSecret, migrate Job, NetworkPolicy, helm-test

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 7.5: `release.yml` — build & deploy

**Files:**
- Create: `.github/workflows/release.yml`

- [ ] **Step 1: Write the workflow**

```yaml
name: release
on:
  push:
    tags: ["v*"]
permissions:
  id-token: write
  contents: read

jobs:
  build-push:
    runs-on: ubuntu-24.04
    outputs:
      image_tag: ${{ steps.tag.outputs.value }}
    steps:
      - uses: actions/checkout@v4
      - id: tag
        run: echo "value=${GITHUB_REF_NAME}" >> $GITHUB_OUTPUT
      - uses: docker/setup-buildx-action@v3
      - uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::${{ vars.AWS_ACCOUNT }}:role/phpenterpriseblog-ci-ecr
          aws-region: ${{ vars.AWS_REGION }}
      - uses: aws-actions/amazon-ecr-login@v2
      - uses: docker/build-push-action@v6
        with:
          context: .
          file: deploy/docker/Dockerfile
          platforms: linux/amd64,linux/arm64
          push: true
          tags: |
            ${{ vars.ECR_REGISTRY }}/phpenterpriseblog:${{ github.ref_name }}
            ${{ vars.ECR_REGISTRY }}/phpenterpriseblog:sha-${{ github.sha }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
          provenance: true
          sbom: true

  deploy-staging:
    needs: build-push
    runs-on: ubuntu-24.04
    environment: staging
    steps:
      - uses: actions/checkout@v4
      - uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::${{ vars.AWS_ACCOUNT }}:role/phpenterpriseblog-cd-eks
          aws-region: ${{ vars.AWS_REGION }}
      - run: aws eks update-kubeconfig --name ${{ vars.EKS_CLUSTER }}
      - uses: azure/setup-helm@v4
      - run: |
          helm upgrade --install phpenterpriseblog deploy/helm/phpenterpriseblog \
            --namespace phpenterpriseblog-staging --create-namespace \
            --values deploy/helm/phpenterpriseblog/values.staging.yaml \
            --set image.repository=${{ vars.ECR_REGISTRY }}/phpenterpriseblog \
            --set image.tag=${{ github.ref_name }} \
            --wait --timeout 5m --atomic

  smoke-staging:
    needs: deploy-staging
    runs-on: ubuntu-24.04
    steps:
      - run: |
          for i in $(seq 1 30); do
            if curl -fsS https://staging.phpenterpriseblog.example.com/healthz | grep -q '"db":"ok"'; then
              echo "Smoke passed"; exit 0
            fi
            sleep 5
          done
          echo "Smoke failed"; exit 1

  deploy-prod:
    needs: smoke-staging
    runs-on: ubuntu-24.04
    environment: production
    steps:
      - uses: actions/checkout@v4
      - uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::${{ vars.AWS_ACCOUNT }}:role/phpenterpriseblog-cd-eks
          aws-region: ${{ vars.AWS_REGION }}
      - run: aws eks update-kubeconfig --name ${{ vars.EKS_CLUSTER }}
      - uses: azure/setup-helm@v4
      - run: |
          helm upgrade --install phpenterpriseblog deploy/helm/phpenterpriseblog \
            --namespace phpenterpriseblog-prod \
            --values deploy/helm/phpenterpriseblog/values.prod.yaml \
            --set image.repository=${{ vars.ECR_REGISTRY }}/phpenterpriseblog \
            --set image.tag=${{ github.ref_name }} \
            --wait --timeout 10m --atomic
```

- [ ] **Step 2: Commit**

```bash
git add .github/workflows/release.yml
git commit -m "cd: release workflow — build (OIDC, multi-arch, SBOM), staging→prod gate

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Phase 8 — E2E + polish

### Task 8.1: Playwright setup

**Files:**
- Create: `tests/E2e/package.json`
- Create: `tests/E2e/playwright.config.ts`

- [ ] **Step 1: Write `package.json`**

```json
{
  "name": "phpenterpriseblog-e2e",
  "private": true,
  "scripts": { "test": "playwright test", "report": "playwright show-report" },
  "devDependencies": { "@playwright/test": "^1.49.0", "typescript": "^5.6.0" }
}
```

- [ ] **Step 2: Write `playwright.config.ts`**

```ts
import { defineConfig } from "@playwright/test";
export default defineConfig({
  testDir: ".",
  timeout: 30_000,
  retries: 0,
  use: {
    baseURL: process.env.BASE_URL ?? "http://localhost:8080",
    trace: "retain-on-failure",
  },
  reporter: [["html", { outputFolder: "playwright-report", open: "never" }]],
});
```

- [ ] **Step 3: Install + verify**

Run: `cd tests/E2e && npm install && npx playwright install chromium`
Expected: chromium downloads.

- [ ] **Step 4: Commit**

```bash
git add tests/E2e/package.json tests/E2e/playwright.config.ts
git commit -m "e2e: playwright project skeleton

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 8.2: Playwright specs

**Files:**
- Create: `tests/E2e/anonymous-can-read.spec.ts`
- Create: `tests/E2e/user-can-write.spec.ts`

- [ ] **Step 1: Write `anonymous-can-read.spec.ts`**

```ts
import { test, expect } from "@playwright/test";

test("anonymous visitor can read the post list and a post", async ({ page }) => {
  await page.goto("/");
  await expect(page).toHaveTitle(/.+/);
  // Whatever the index lists, at least one post link should exist after seed
  await expect(page.locator("a[href^='/posts/']").first()).toBeVisible();
  await page.locator("a[href^='/posts/']").first().click();
  await expect(page.url()).toMatch(/\/posts\/\d+/);
});
```

- [ ] **Step 2: Write `user-can-write.spec.ts`**

```ts
import { test, expect } from "@playwright/test";

test("user can sign up, create a post, log out", async ({ page }) => {
  const email = `e2e-${Date.now()}@example.com`;

  await page.goto("/auth/signup");
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', "secret123");
  await page.click('button[type="submit"], input[type="submit"]');

  await page.goto("/posts/create");
  await page.fill('input[name="title"]', "E2E title");
  await page.fill('textarea[name="message"], input[name="message"]', "E2E body");
  await page.click('button[type="submit"], input[type="submit"]');

  await page.goto("/");
  await expect(page.locator("body")).toContainText("E2E title");

  await page.goto("/auth/logout");
  await expect(page).toHaveURL(/\/auth\/login$/);
});
```

- [ ] **Step 3: Run against local stack**

Run: `make up && cd tests/E2e && npm install && npx playwright test`
Expected: both specs pass.

- [ ] **Step 4: Commit**

```bash
git add tests/E2e/*.spec.ts
git commit -m "e2e: anonymous-read + user-write smoke specs

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 8.3: Ratchet PHPStan to level 8

- [ ] **Step 1: Bump level**

Edit `phpstan.neon`: `level: 8`.

- [ ] **Step 2: Regenerate baseline**

Run: `vendor/bin/phpstan analyse --memory-limit=512M --generate-baseline=phpstan-baseline.neon`

- [ ] **Step 3: Walk through baseline, fix what can be fixed quickly**

Open `phpstan-baseline.neon`. For each issue:
- If it's a missing return type or property type → fix in source.
- If it's a `mixed` that should be a typed array shape → add `@param` / `@return` PHPDoc.
- If it's a third-party stub issue → leave in baseline.

Re-run `composer stan` after each fix until baseline shrinks meaningfully.

- [ ] **Step 4: Commit**

```bash
git add phpstan.neon phpstan-baseline.neon src/
git commit -m "stan: ratchet PHPStan to level 8 and tighten types

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 8.4: Architecture Decision Records

**Files:**
- Create: `docs/adr/0001-composer-psr4.md`
- Create: `docs/adr/0002-models-vs-repositories.md`
- Create: `docs/adr/0003-forward-only-migrations.md`
- Create: `docs/adr/0004-helm-atomic-releases.md`

- [ ] **Step 1: Use the `adr` skill to generate each ADR**

For each topic, invoke the `adr` skill to scaffold the file with Context / Decision / Consequences sections. Content summary per ADR:
- **0001:** Why PSR-4 with `src/` over the legacy hand-rolled autoloader. Trade-off: vendor footprint vs every PHP tool in the ecosystem.
- **0002:** Why Models are DTOs and SQL lives in Repositories. Trade-off: more files for clarity & test isolation.
- **0003:** Migrations are append-only and never destructive in the same release as their replacement. Rollback safety > schema-churn convenience.
- **0004:** All helm releases use `--atomic`. Reason: a failed migration must not leave a partial release; full rollback is automated.

- [ ] **Step 2: Commit**

```bash
git add docs/adr/
git commit -m "docs(adr): 0001..0004 — Composer PSR-4, repos pattern, fwd-only migrations, atomic helm

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 8.5: Final README

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Rewrite `README.md`**

```markdown
# phpenterpriseblog

PHP 8.5 enterprise-shaped remake of the Udemy `freeblog` MVC course project.

## Quickstart
```bash
make up         # docker compose up + migrate
open http://localhost:8080
```

## Stack
- PHP 8.5-fpm-alpine, nginx 1.27
- MySQL 8.4 / RDS in prod
- Redis 7 / ElastiCache (TLS) in prod
- Deployed via Helm to AWS EKS

## Quality
- PHPStan level 8
- PSR-12 + Slevomat strict typing
- PHPUnit (unit + integration) + Playwright (smoke E2E)
- CI on PHP 8.4 *and* 8.5

## Commands
| `make ...` | what |
|---|---|
| `up` / `down` | start / stop local stack |
| `test` / `test-all` / `ci` | unit / unit+integration / cs+stan+tests |
| `migrate` / `migrate-fresh` | apply migrations / drop+reapply |
| `e2e` | Playwright specs |

## Releases
Tag `v*` on `main` → GitHub Actions builds + deploys staging → manual approval → prod.

See `docs/superpowers/specs/2026-06-17-php85-modernization-design.md` for the full design and `docs/adr/` for decision records.
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs(readme): rewrite with quickstart, stack, commands, release flow

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

### Task 8.6: Build course PDFs

**Files:**
- Run-only — no new files in `phpenterpriseblog`. Outputs in `../phpblog-udemy/pdf/`.

**Interfaces:**
- Consumes: every lesson markdown written across Phases 1–8 plus Module 00.
- Produces: one PDF per lesson under `../phpblog-udemy/pdf/<module>/<lesson>.pdf`.

- [ ] **Step 1: Install prerequisites if missing**

```bash
command -v pandoc  >/dev/null || brew install pandoc
command -v xelatex >/dev/null || brew install --cask mactex-no-gui   # ~3.5 GB, one-time
```

- [ ] **Step 2: Build all PDFs**

```bash
cd ../phpblog-udemy
make -C build all
ls pdf/**/*.pdf | head
```
Expected: each lesson produces a PDF in `pdf/<module>/<lesson>.pdf`.

- [ ] **Step 3: Commit (in the course repo)**

```bash
cd ../phpblog-udemy
git add pdf/   # .gitignore excludes pdf/*.pdf; this commits .gitkeep updates only
git commit --allow-empty -m "course: build PDFs for all lessons (artifacts gitignored)

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
cd -
```

- [ ] **Step 4: Tag the source repo for the final state**

```bash
git tag lesson-8-6
git tag v0.1.0-course
```

---

## Final verification

- [ ] **All green locally:**
  ```bash
  make down
  make up
  make ci
  make test-all
  make e2e
  curl -s localhost:8080/healthz | grep '"db":"ok"'
  ```
- [ ] **Tag a release candidate to exercise the pipeline end-to-end:**
  ```bash
  git tag v0.1.0-rc1
  git push --tags
  ```
  Watch `release.yml` in Actions. Confirm staging deploys, smoke passes, prod gate waits for approval.

---

## Self-review checklist (for the worker before opening final PR)

- [ ] Every URL in the original `freeblog` routes still resolves in `config/routes.php`.
- [ ] `PostRepository::save` writes `message` (not `email`) — and the regression test pins it.
- [ ] No `mysqli` references anywhere in `src/`.
- [ ] All classes in `src/` have `declare(strict_types=1);`.
- [ ] `composer ci` is green; `composer stan` at level 8.
- [ ] `helm lint deploy/helm/phpenterpriseblog` clean.
- [ ] `docker build` succeeds on a clean clone.
- [ ] `make e2e` passes locally.
- [ ] No file in `src/Models/` contains SQL.
- [ ] No file in `resources/views/` echoes user-supplied data without `htmlspecialchars`.
