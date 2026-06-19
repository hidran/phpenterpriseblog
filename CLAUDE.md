# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`phpenterpriseblog` is a PHP 8.5, enterprise-shaped remake of the `freeblog` Udemy MVC blog. The original lives at `../freeblog` (behavioral reference only — every file here is newly authored). It was built task-by-task from `docs/superpowers/plans/2026-06-17-php85-modernization.md` (the full design + global constraints); that plan is now **fully implemented** (Phases 1–8). The companion Udemy course lives in the sibling repo `../phpblog-udemy/` (one lesson per task).

Current state: complete and green. `composer ci` passes (PSR-12 + Slevomat strict typing, **PHPStan level 8**, PHPUnit unit+integration), Playwright e2e passes, the Docker dev stack + Redis cache/sessions work, GitHub Actions CI is green on PHP 8.4 **and** 8.5, and the Helm/EKS CD plus a live-tested CloudFormation demo kit exist under `deploy/`.

## Commands

```bash
composer ci          # the full gate: cs + stan + test:all
composer cs          # phpcs (PSR-12 + Slevomat); cs:fix = php-cs-fixer
composer stan        # phpstan, level 8, against phpstan-baseline.neon
composer test        # phpunit --testsuite=unit
composer test:int    # phpunit --testsuite=integration (needs live MySQL+Redis)
composer rector      # rector dry-run; rector:fix to apply
```
Single test: `vendor/bin/phpunit tests/Unit/Http/RouterTest.php` or `--filter testMethodName`.

Local dev runs in Docker via the `Makefile` (targets exec inside the `app` container):
```bash
composer install          # REQUIRED before `make up` on a fresh clone — see note below
make up                   # docker compose up -d --wait + migrate
make seed                 # load demo data (database/seeds)
make ci / test / stan / cs / e2e
make migrate / migrate-fresh
make down                 # stop + remove volumes
```
> **Fresh-clone note:** run `composer install` before `make up`. The `app` service bind-mounts the repo over `/var/www/html`, and the image's `opcache.preload` requires `vendor/autoload.php`; with no `vendor/` on the host, php-fpm exits 70 and the container never becomes healthy. (CI's e2e job installs deps for the same reason.)

Migrations are forward-only, tracked in a `migrations` table: `bin/console migrate [--fresh]`. Without Docker you can serve directly: `php -S localhost:8080 -t public` (entry point `public/index.php`).

## Architecture

Request flow: `public/index.php` → `App\Kernel::handle()` per request: load `.env` (vlucas/phpdotenv, immutable) → `configureSession()` (Redis session handler only when `SESSION_DRIVER=redis-tls`, else PHP files) → `session_start()` → build a `League\Container` (with a `ReflectionContainer` delegate so **constructor deps autowire**) → build a PSR-7 `ServerRequest` (nyholm/psr7) → dispatch through `App\Http\Router` → emit the returned `ResponseInterface` (note the `Set-Cookie` append handling in `emit()`).

Routing: `config/routes.php` is a plain `['GET' => ['path' => [Controller::class, 'method']]]` table with `:id`-style placeholders. `App\Http\Router` wraps `league/route`: rewrites `:id` → `{id}`, maps each entry under `ApplicationStrategy` (autowires controllers from the container). `Kernel` catches `NotFoundException`/`MethodNotAllowedException` → 404/405.

Layers (thin controllers → services → repositories):
- **Controllers** (`src/Controllers/`) extend `BaseController` (`respond()` wraps body in `layouts/default`; `redirect()`; `json()` — all return PSR-7). Action signature `(ServerRequestInterface $request, array $args = [])`; route params arrive in `$args`. `HealthController` serves `GET /healthz` (db + redis probes, 200/503).
- **Repositories** (`src/Repositories/`) take a `PDO`, own all SQL, hydrate `Model::fromRow(...)`. `UserRepository` implements `UserRepositoryInterface` so `AuthService` can be unit-tested with a mock (a `final` class can't be doubled).
- **Models** (`src/Models/`) are `final readonly` typed entities with a `fromRow()` factory; **no SQL**.
- **Services** (`src/Services/`) hold domain logic; `AuthService` returns an `AuthResult` value object instead of throwing for expected validation failures.
- **View** (`src/Support/View.php`) renders `resources/views/<name>.tpl.php` via output buffering + `extract()`; plain-PHP templates.
- **Cache** (`src/Cache/`) — `RedisCache` (PSR-16 over symfony/cache) behind `App\Cache\CacheInterface`; `CachedPostRepository` decorates `PostRepository` (read-through + write invalidation). PSR-16 reserves `{}()/\@:` in keys, so keys are dot-separated (`posts.list.v1`).
- **Session** (`src/Session/RedisSessionHandler.php`) — Redis-backed `SessionHandlerInterface` for the production TLS path.

Database: `ConnectionFactory::fromEnv()` builds the DSN from `DB_*`; `PdoConnection` **hard-enforces** `ERRMODE_EXCEPTION` and `EMULATE_PREPARES => false` regardless of caller options. Schema is utf8mb4/InnoDB with FKs (FK columns and their referenced PKs must match exactly, incl. `UNSIGNED`). Note MySQL **implicitly commits on DDL**, so `MigrateCommand` guards `commit()`/`rollBack()` with `inTransaction()`.

Deploy (`deploy/`): `docker/Dockerfile` (multi-stage php:8.5-fpm-alpine + opcache/JIT; opcache & mbstring are already bundled in the base image — only pdo_mysql/intl/redis are added); `helm/phpenterpriseblog/` (chart for EKS); `aws/` (prereqs README + a `cloudformation/` + `presentation/` up.sh/down.sh demo kit). CI/CD: `.github/workflows/ci.yml` (quality matrix + integration + e2e) and `release.yml` (tag `v*` → OIDC build → staging → prod).

## Project-specific constraints

- **PHP floor is `^8.4`**; CI runs 8.4 and 8.5, both must pass. Don't use 8.5-only features.
- **PHPStan is at level 8**; the baseline (`phpstan-baseline.neon`) may shrink but must not grow in a PR. Don't silence with casts/`@var`/widened types — fix the cause or leave genuine third-party-stub items baselined.
- **Legacy URL surface must keep working**: `/`, `/posts`, `/posts/{id}`, `/posts/create`, `/posts/{id}/edit`, `/posts/{id}/comments`, `/auth/{login,signup,logout}`.
- **Migrations are forward-only** — never drop a column in the same release that introduces its replacement.
- **Namespace root** PSR-4 `App\` → `src/`; helpers via Composer `files`. Tests `Tests\` → `tests/`. PHPCS/PHPStan exclude `resources/views`, `database`, `tests/Fixtures`, `tests/E2e` (templates use `extract()`; E2E is JS/TS).

## Commit conventions

Commits are authored as `Hidran Arias <hidran@gmail.com>`, message format `<area>: <short imperative>` (e.g. `cache: add PSR-16 RedisCache`), with a `Co-Authored-By: Claude Opus <version> (1M context) <noreply@anthropic.com>` trailer. Each plan task was one commit, tagged `lesson-<phase>-<task>`, with a matching lesson committed in `../phpblog-udemy/`; the plan is complete, so for new work just follow the commit format (the lesson discipline only applied to plan tasks).
