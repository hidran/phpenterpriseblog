# Design — PHP 8.5 modernization of `freeblog` → `phpinterpriseblog`

- **Date:** 2026-06-17
- **Author:** Hidran Arias (with Claude)
- **Status:** Approved (brainstorming complete; implementation plan pending)
- **Source project:** `/Users/hidranarias/projects/udemy/freeblog` (Udemy course)
- **Target project:** `/Users/hidranarias/projects/udemy/phpinterpriseblog` (this repo)

## 1. Summary

Rebuild the Udemy `freeblog` course project as a PHP 8.5, enterprise-shaped application: PSR-4 with Composer, separation of concerns (Models / Repositories / Services / Http / Cache / Session), Redis-backed sessions and data cache, Docker-packaged, deployed to AWS EKS via a Helm chart driven by GitHub Actions with OIDC auth. The legacy app's external behavior (URLs, response shapes, DB schema columns) is preserved so the course narrative stays intact; only the internals are modernized.

## 2. Context

`freeblog` is a hand-rolled MVC blog (~600 LOC) that exists to teach PHP from first principles: custom `spl_autoload_register` autoloader, custom router, PDO singleton, three controllers, three models, plain-PHP templates. It has no Composer, no tests, no env configuration, several PHP 8.5-deprecation-prone patterns (untyped properties, implicit nullable parameters), one real bug in `PostController::save` (writes `email` value into the `message` column), and dead `mysqli`-based code in `User::delete`/`User::getUser`. It runs on bare `php -S` / Apache against a local MySQL.

The user wants a production-shaped remake — same product, modern foundation — without throwing away the pedagogy at the routing/view layer.

## 3. Goals

1. **PHP 8.5 compatible** (CI matrix proves 8.4 + 8.5 dual builds).
2. **Modern project layout** with PSR-4, a single `src/` namespace root, and clear separation of Controllers / Models (entities) / Repositories (SQL) / Services (business logic) / Http / Cache / Session.
3. **Quality tooling that runs in CI:** Composer scripts, PHP_CodeSniffer (PSR-12 + Slevomat strict typing), PHPStan (level 6 → ratchet to 8), Rector (one-shot per phase), PHPUnit (unit + integration), Playwright (E2E smoke).
4. **Local dev in one command** (`make up`): Docker-compose stack with PHP-FPM + nginx + MySQL 8 + Redis + MailHog.
5. **Caching that matters:** Redis-backed PSR-16 cache decorating the Post repository, Redis-backed PHP sessions, opcache + tracing JIT enabled in the production image.
6. **CI/CD on GitHub Actions → ECR → EKS:** OIDC auth (no long-lived keys), Helm chart with ExternalSecrets, two-environment promotion (staging → manual approval → prod), atomic helm releases with auto-rollback, pre-upgrade migration Jobs.
7. **Secure-by-default runtime:** non-root pod, read-only root filesystem, dropped capabilities, network policy, signed images with SBOM.
8. **Observable enough to operate:** `/healthz` endpoint, stdout/stderr logs to CloudWatch, kube-state metrics.

## 4. Non-goals

- Rewriting the URL surface, view templates, or DB schema columns (only renames described in §6).
- A microframework migration (Slim/Symfony/Laravel) — deliberately rejected; defeats the course's pedagogical core.
- Full edge HTTP caching (CloudFront / Varnish). Out of scope; revisit when read volume justifies it.
- Distributed tracing / OpenTelemetry. Out of scope; logging + metrics ship first.
- Multi-region / DR.
- Email delivery (MailHog only locally; production SES wiring deferred to a follow-up).

## 5. Decisions captured during brainstorming

| # | Question | Decision | Rationale |
|---|---|---|---|
| 1 | Composer or hand-rolled autoloader? | **Full modernization, Composer + PSR-4** | Unlocks every other ask; standard PHP path. |
| 2 | Where will production run? | **Kubernetes / cloud-native** | User chose K8s over VPS / shared / local-only. |
| 3 | Cloud target? | **AWS / EKS** | User picked EKS over GKE / agnostic Helm. |
| 4 | Caching layers? | **Redis** — data cache + session storage; opcache + JIT in image | Right layers for a blog; horizontal-scale ready. |
| 5 | Test depth? | **Full pyramid:** unit + integration + smoke E2E | User chose deepest option for portfolio quality. |
| 6 | Approach? | **Approach A — staged, merge-by-merge** | Each phase reviewable and revertable independently. |
| 7 | `postscomments` → `post_comments` rename? | **Yes — rename** | User-approved. Schema cleanup belongs in phase 1. |
| 8 | Project layout shape? | **Best-practice remake** (single `src/`, Models split from Repositories, Services extracted, Kernel + Container) | User explicitly asked for best practices over minimal lowercasing. |

## 6. Target architecture

### 6.1 Directory layout

```
phpinterpriseblog/
├── .github/workflows/                ci.yml, release.yml
├── bin/
│   ├── console                       CLI entry (migrate, cache:flush, db:seed)
│   └── migrate                       thin wrapper around console
├── config/                           pure config; reads only from $_ENV
│   ├── app.php
│   ├── database.php
│   ├── cache.php
│   ├── session.php
│   └── routes.php
├── database/
│   ├── migrations/
│   │   └── 0001_init.sql             utf8mb4, `post_comments` renamed
│   └── seeds/0001_demo.sql
├── deploy/
│   ├── aws/README.md                 one-time AWS prerequisites (OIDC, IAM, ECR, RDS, ElastiCache)
│   ├── docker/{Dockerfile,nginx.conf,php.ini,php-fpm.conf,preload.php}
│   └── helm/phpinterpriseblog/       Helm chart (Chart, values, templates)
├── docs/
│   ├── adr/                          Architecture Decision Records
│   └── superpowers/specs/            this design + future specs
├── public/                           web root (entry + assets only)
│   ├── index.php                     ~10 lines: autoload + Kernel boot
│   ├── .htaccess
│   └── assets/{css,js,img}/
├── resources/views/
│   ├── layouts/default.tpl.php
│   └── pages/
│       ├── posts/{index,show,create,edit}.tpl.php
│       └── auth/{login,signup}.tpl.php
├── src/                              ← all application code, namespace App\
│   ├── Kernel.php
│   ├── Container/Container.php       PSR-11 (league/container)
│   ├── Http/{Router.php,Request.php,Response.php}
│   ├── Controllers/{BaseController.php,PostController.php,AuthController.php}
│   ├── Models/{Post.php,User.php,Comment.php}            thin DTOs, no DB
│   ├── Repositories/{PostRepository.php,UserRepository.php,CommentRepository.php}
│   ├── Services/AuthService.php
│   ├── Database/{ConnectionFactory.php,PdoConnection.php}
│   ├── Cache/{CacheInterface.php,RedisCache.php,CachedPostRepository.php}
│   ├── Session/{SessionInterface.php,RedisSessionHandler.php}
│   └── Support/{Env.php,View.php,helpers.php}
├── tests/
│   ├── Unit/                         no I/O
│   ├── Integration/                  MySQL + Redis services
│   ├── E2e/                          Playwright specs (TS)
│   ├── Fixtures/
│   └── bootstrap.php
├── .env.example
├── composer.json                     PSR-4: "App\\": "src/"
├── docker-compose.yml                local dev only
├── Makefile
├── phpstan.neon
├── phpcs.xml
├── phpunit.xml.dist
├── playwright.config.ts
└── README.md
```

### 6.2 Architectural deltas from `freeblog`

| Concern | `freeblog` | `phpinterpriseblog` |
|---|---|---|
| Autoload | custom `loadClass` string-replace | Composer PSR-4 (`App\` → `src/`) |
| Bootstrap | logic in `public/index.php` | `public/index.php` is ~10 lines; `App\Kernel` owns lifecycle |
| Config | hard-coded credentials in `config/database.php` | `.env` + `vlucas/phpdotenv`; config files read only `$_ENV` |
| Routing | inline | `App\Http\Router` + `config/routes.php`; typed Request/Response objects |
| Data access | SQL mixed into Model classes | thin Model DTOs + `*Repository` classes own SQL |
| Auth | inside `LoginController` | `App\Services\AuthService`; controller is thin |
| Cache | none | PSR-16 `RedisCache` + `CachedPostRepository` decorator |
| Sessions | filesystem | `App\Session\RedisSessionHandler` (TLS for ElastiCache) |
| Wiring | `new` everywhere | PSR-11 container (`league/container`) |
| Views | mixed under `app/views/` | `resources/views/{layouts,pages}/...` |
| CLI | none | `bin/console` runs migrations, cache flush, seeds |
| Migrations | one dump SQL file | numbered SQL migrations + tracking table |

### 6.3 Renames

- Folders: `controllers|models|core|db|helpers` consolidated under `src/`; `views` moved to `resources/views/`.
- Classes: `DbFactory` → `ConnectionFactory`, `DbPdo` → `PdoConnection`, `LoginController` → `AuthController`.
- DB table: `postscomments` → `post_comments`.
- All external behavior (URLs, response shapes, DB column names) preserved.

### 6.4 PHP 8.5 hardening (concrete fixes)

- `BaseController`: type the untyped properties (`protected string $content = ''`, `protected string $layout`).
- `User` model (old code): delete `delete()` and `getUser()` — broken `mysqli`-via-`$GLOBALS` dead code. Type `saveUser(array $data): array`.
- `AuthController` (was `LoginController`): type `verifyLogin(string,string,string): array`, `verifySignup(...)`, `generateToken(): string`.
- `PostController::save`: **bug fix** — writes `$post['email']` into `message` column. Becomes a regression test.
- `Router`: type-narrow `parse_url()` (returns `string|false|null`) for PHPStan.
- `core/bootstrap.php`: removed; replaced by Composer autoload + `App\Kernel`.
- `database/freeblog.sql`: `utf8mb3` → `utf8mb4`; `postscomments` → `post_comments`.

## 7. Tooling & testing

### 7.1 Composer dependencies

**Runtime:**
- `php: ^8.4` (8.5 in CI matrix; floor at 8.4 for portability)
- `ext-pdo`, `ext-pdo_mysql`, `ext-redis`, `ext-mbstring`, `ext-json`, `ext-intl`
- `vlucas/phpdotenv: ^5.6`
- `psr/simple-cache: ^3.0`, `symfony/cache: ^7.2`
- `psr/log: ^3.0`, `monolog/monolog: ^3.8`
- `psr/container: ^2.0`, `league/container: ^4.2`

**Dev:**
- `phpunit/phpunit: ^11.5`
- `phpstan/phpstan: ^2.1`, `phpstan/extension-installer`, `phpstan/phpstan-phpunit`
- `squizlabs/php_codesniffer: ^3.11`, `slevomat/coding-standard`
- `friendsofphp/php-cs-fixer: ^3.68`
- `rector/rector: ^2.0`

### 7.2 Composer scripts

```json
"scripts": {
  "cs":         "phpcs",
  "cs:fix":     "php-cs-fixer fix",
  "stan":       "phpstan analyse --memory-limit=512M",
  "test":       "phpunit --testsuite=unit",
  "test:int":   "phpunit --testsuite=integration",
  "test:all":   "phpunit",
  "rector":     "rector process --dry-run",
  "rector:fix": "rector process",
  "ci":         ["@cs", "@stan", "@test:all"]
}
```

### 7.3 PHPStan

- Start **level 6**, ratchet up to 7 → 8 over subsequent phases.
- Generate `phpstan-baseline.neon` once on day one; PRs must not grow it.
- Paths: `src/`, `bin/`, `config/`, `public/index.php`, `tests/`.

### 7.4 PHP_CodeSniffer

- Base PSR-12 + `SlevomatCodingStandard.TypeHints.{Parameter,Return,Property}TypeHint`.
- Excludes `vendor/`, `database/`, `resources/views/`.
- Auto-fixer via `composer cs:fix`.

### 7.5 Rector

- Sets: `LevelSetList::UP_TO_PHP_85`, `SetList::CODE_QUALITY`, `SetList::DEAD_CODE`, `SetList::TYPE_DECLARATION`.
- One-shot per phase, manually reviewed. **Not** a CI gate.

### 7.6 PHPUnit

Two suites in `phpunit.xml.dist`:

- **`unit`** (no I/O, <2s in CI):
  - `RouterTest` — exact + parameterized routes, 404 throws.
  - `AuthServiceTest` — verifyLogin/verifySignup outcomes (mocked `UserRepository`).
  - `ViewTest` — renders template into string.
  - `EnvTest`, `RedisCacheTest` (against `ArrayAdapter`).
  - `PostController::save` bug regression test (mocked repository).
- **`integration`** (MySQL 8 + Redis services in CI, ~30s):
  - `PostRepositoryIntegrationTest` — all CRUD round-trips.
  - `UserRepositoryIntegrationTest` — including unique-email constraint.
  - `CachedPostRepositoryIntegrationTest` — first call misses, second hits, save() invalidates.
  - `RedisSessionHandlerIntegrationTest` — write/read/regenerate.
  - Bootstrap drops & recreates DB from `database/migrations/*.sql` before each test class.

### 7.7 Playwright E2E

Two specs only in `tests/E2e/`:
1. `anonymous-can-read.spec.ts` — visit `/`, see post list, click a post, see comments form rejects when not logged in.
2. `user-can-write.spec.ts` — signup → login → create post → see it on the index → log out.

Runs against the full docker-compose stack in CI. Single chromium browser, no matrix.

### 7.8 Makefile (developer DX)

```
make up | down | sh | test | test-all | ci | stan | cs | cs-fix | migrate | migrate-fresh | e2e | install-hooks
```

## 8. Local dev, Docker & caching

### 8.1 Production image (`deploy/docker/Dockerfile`)

Two stages: `composer:2 → php:8.5-fpm-alpine`. Includes `pdo_mysql`, `opcache`, `intl`, `mbstring`, `redis` (pecl). Runs as non-root `www-data`. ~80 MB.

### 8.2 `php.ini` (production)

- `display_errors=Off`, `log_errors=On`, `error_log=/proc/self/fd/2`.
- `opcache.enable=1`, `validate_timestamps=0`, `memory_consumption=256`, `interned_strings_buffer=16`, `max_accelerated_files=20000`.
- `opcache.preload=/var/www/html/deploy/docker/preload.php` (autoload classmap warmer).
- **JIT:** `opcache.jit=tracing`, `opcache.jit_buffer_size=128M`.
- `session.save_handler=redis`, `session.save_path="tcp://redis:6379?auth=...&database=1"`, `cookie_httponly=1`, `cookie_samesite=Lax`, `use_strict_mode=1`.

### 8.3 `docker-compose.yml` (local dev)

Services: `app` (PHP-FPM from local Dockerfile, bind-mounted), `nginx` (1.27-alpine, port 8080), `mysql` (8.4 with healthcheck), `redis` (7-alpine with password), `mailhog` (port 8025). MySQL auto-applies `database/migrations/*.sql` on first boot via `/docker-entrypoint-initdb.d`.

### 8.4 Redis caching strategy

- `App\Cache\RedisCache` implements PSR-16 over `symfony/cache`'s `RedisAdapter`.
- **`CachedPostRepository`** decorator wraps `PostRepository`:
  - `all()` → key `posts:list:v1`, TTL 60s.
  - `findByPostId(int $id)` → key `posts:show:{id}:v1`, TTL 300s.
  - `save()` / `update()` / `delete()` → forward to inner, then invalidate both keys.
- Cache key suffix `:v1` lets schema changes cut over without manual Redis flush.

### 8.5 Sessions

- Local: `session.save_handler=redis` (zero PHP code, set in `php.ini`).
- Production (ElastiCache TLS): `App\Session\RedisSessionHandler implements SessionHandlerInterface`, registered in `Kernel::boot()`. Selected via `SESSION_DRIVER=redis-tls`.

### 8.6 Migrations

`bin/console migrate` — reads `database/migrations/*.sql` in lex order, tracks applied filenames in a `migrations` table, runs new ones in a transaction. ~80 LOC, no external library.

Used in three contexts:
- **Local:** `make migrate` after `make up`.
- **CI integration suite:** runs in test bootstrap.
- **Production:** Helm `pre-upgrade,pre-install` Job runs against RDS.

### 8.7 Healthcheck

`GET /healthz` returns `{"db":"ok","redis":"ok","version":"<git-sha>"}`. Used by nginx readiness/liveness probes and compose healthchecks.

## 9. CI/CD

### 9.1 One-time AWS prerequisites (documented in `deploy/aws/README.md`)

- ECR repo `phpinterpriseblog`.
- IAM OIDC provider trusting `token.actions.githubusercontent.com`.
- Two OIDC-assumable roles (no long-lived keys):
  - `phpinterpriseblog-ci-ecr` — `ecr:Put*` on `repo/phpinterpriseblog` only, ref-restricted to `main` + tags `v*`.
  - `phpinterpriseblog-cd-eks` — `eks:DescribeCluster` + `secretsmanager:GetSecretValue` on `phpinterpriseblog/*`, tag-restricted to `v*`.
- EKS cluster (≥ 1.30); namespaces `phpinterpriseblog-staging`, `phpinterpriseblog-prod`.
- RDS MySQL 8 per env. Connection in AWS Secrets Manager (`phpinterpriseblog/{env}/db`).
- ElastiCache Redis per env (TLS on). Secret `phpinterpriseblog/{env}/redis`.
- External Secrets Operator + AWS Load Balancer Controller installed cluster-wide.

### 9.2 `ci.yml` (every PR + push to `main`)

Three jobs:
- **`quality`** matrix on PHP 8.4 + 8.5: `composer cs`, `composer stan`, `composer test` (unit).
- **`integration`**: MySQL + Redis services, runs `php bin/console migrate` then `composer test:int`.
- **`e2e`**: brings up the docker-compose stack with `--wait`, runs Playwright against it. Uploads HTML report artifact.

Merge gate on `main`: all three green.

### 9.3 `release.yml` (on tag `v*`)

OIDC-authed pipeline with four jobs:
1. **`build-push`** — `docker buildx` multi-arch (amd64, arm64), pushed to ECR with SBOM + provenance attestations. Tags: `v*` + `sha-*`.
2. **`deploy-staging`** — `helm upgrade --install --wait --atomic --values values.staging.yaml --set image.tag=<tag>`. Uses GitHub Environment `staging` (no approval).
3. **`smoke-staging`** — `curl --retry 30 https://staging.../healthz | grep '"db":"ok"'`.
4. **`deploy-prod`** — same as staging but `values.prod.yaml`, namespace `freeblog-prod`, GitHub Environment `production` with **required reviewers**.

### 9.4 Helm chart (`deploy/helm/phpinterpriseblog/`)

Templates:
- `deployment.yaml` — two-container Pod: `nginx` + `php-fpm`, shared emptyDir for socket.
- `service.yaml`, `ingress.yaml` (ALB + ACM cert, HTTPS-only).
- `configmap-{nginx,php}.yaml`.
- `externalsecret.yaml` — ESO pulls from AWS Secrets Manager into a `Secret`.
- `serviceaccount.yaml` — IRSA-bound role for SecretsManager reads (defense in depth).
- `hpa.yaml` — min 2 / max 10, target 70 % CPU.
- `pdb.yaml` — `minAvailable: 1`.
- `job-migrate.yaml` — `pre-install,pre-upgrade` hook running `bin/console migrate`.
- `networkpolicy.yaml` — default-deny + egress to RDS:3306, Redis:6379, DNS.
- `tests/test-connection.yaml` — `helm test` posts `/healthz`.

Security defaults applied to all pods:
- `runAsNonRoot: true`, `runAsUser: 33`, `fsGroup: 33`.
- `readOnlyRootFilesystem: true`, `allowPrivilegeEscalation: false`, `capabilities.drop: [ALL]`.
- `seccompProfile.type: RuntimeDefault`.

### 9.5 Rollback strategy

- **Auto on failure:** `helm upgrade --atomic` rolls back chart + pods when probes fail or the migration Job fails.
- **Manual:** `helm rollback phpinterpriseblog <REV> -n phpinterpriseblog-prod`.
- **Image:** retag a known-good ECR image and re-trigger `release.yml`.
- **Migrations are forward-only & backwards-compatible** (two-step strategy for renames). Documented in `docs/adr/0003-forward-only-migrations.md`.

### 9.6 Observability (minimum)

- Logs: stdout/stderr → CloudWatch Logs via EKS Container Insights.
- Metrics: kube-state-metrics + node-exporter → CloudWatch.
- Tracing: deferred.

### 9.7 Branch protection on `main`

- Require PR review.
- Require CI workflow green (`quality`, `integration`, `e2e`).
- Require linear history.
- Dismiss stale approvals on push.

## 10. Migration plan from `freeblog` → `phpinterpriseblog` (high level)

The detailed step-by-step plan with verification gates is produced by the `writing-plans` skill (separate document). The high-level shape is the eight phases from Approach A:

1. **Foundation:** Composer + PSR-4, new `src/` layout, port code with renames, delete dead `mysqli` code, fix `PostController::save` bug, rename `postscomments` → `post_comments`. PHP 8.5 type hardening pass.
2. **Quality gates:** PHPStan + PHPCS + Rector wired; baselines generated; first-pass cleanup.
3. **Unit tests:** PHPUnit + first unit suite (Router, AuthService, View, RedisCache, regression test for the save bug).
4. **Local dev image:** Dockerfile, nginx + php-fpm + MySQL + Redis docker-compose, Makefile, healthcheck endpoint, migrations runner.
5. **Caching:** PSR-16 RedisCache, `CachedPostRepository`, Redis session handler, opcache + JIT tuning.
6. **CI:** `ci.yml` (quality matrix, integration, e2e). Branch protection enabled.
7. **CD:** Helm chart + `release.yml`. Staging deploy + smoke + prod gate.
8. **Smoke E2E + polish:** Playwright specs, README, ADRs (0001–0003 minimum), final PHPStan ratchet to level 8.

Each phase is a separate PR. Each PR ships independently (the app keeps working after every phase).

## 11. Out of scope

See §4. Notable deferrals: edge HTTP caching, distributed tracing, multi-region, email delivery in production, in-app admin UI, RBAC beyond the existing `roletype` column.

## 12. Risks & open questions

- **R1 — Composer PSR-4 case-sensitivity on Linux.** Today's `freeblog` mixes `App\Views` (autoload string) with `app/views` (filesystem). On macOS it works; in containers it breaks. Mitigation: phase 1 standardizes casing in one commit.
- **R2 — `LoginController` rename to `AuthController` breaks bookmarked URLs?** No — URLs are decided by `routes.php`, not class names. Behavior unchanged.
- **R3 — ElastiCache TLS gotchas.** `php-redis` requires `tls://` scheme + correct CA. Mitigation: integration test in CI uses plaintext; production handler tested in a one-off `helm test` on first deploy.
- **R4 — Migrations rolling back on `--atomic` failure leave RDS forward-migrated.** This is intentional (forward-only policy). Documented in ADR-0003. If feared, add a `pre-rollback` hook that re-runs `migrate` from the prior chart's image to no-op against an already-migrated DB.
- **R5 — Cost.** EKS + RDS + ElastiCache for a personal blog is ~$150/month minimum. If cost is a concern, consider downgrading to a single EC2 + docker compose; the Dockerfile and Helm chart values are designed so this swap is a values-only change at the deploy layer.
- **OQ1 — Project name spelling.** The user wrote `phpinterpriseblog` (not `phpenterpriseblog`). Treated as intentional; will rename if confirmed typo.
- **OQ2 — Domain name.** Spec uses `phpinterpriseblog.example.com` / `staging.phpinterpriseblog.example.com` as placeholders. Replace before first deploy.

## 13. Success criteria

The remake is "done" when **all** of these are true:

1. `make up && make ci && make e2e` runs green on a fresh clone.
2. `composer stan` passes at PHPStan level 8 with no baseline growth.
3. `composer cs` passes at PSR-12 + Slevomat strict typing rules.
4. PHPUnit unit + integration suites green; coverage report ≥ 70 % line coverage on `src/`.
5. Playwright specs green in CI against the full compose stack.
6. CI matrix proves PHP 8.4 *and* 8.5 builds in the same workflow run.
7. A tag push deploys to staging automatically, runs smoke checks, and waits at a manual gate for prod.
8. `helm rollback` succeeds end-to-end on a deliberately broken release.
9. The legacy `freeblog` URL surface still works (`/`, `/posts`, `/posts/{id}`, `/posts/create`, `/posts/{id}/edit`, `/posts/{id}/comments`, `/auth/login`, `/auth/signup`, `/auth/logout`).
10. The legacy `PostController::save` bug (writing email into the message column) is fixed and a regression test exists.

## 14. References

- Source code analysis: `freeblog` working tree as of 2026-06-17.
- Brainstorming decisions: this document, §5.
- ADRs to be written during implementation:
  - `0001-composer-psr4-autoload.md`
  - `0002-models-vs-repositories.md`
  - `0003-forward-only-migrations.md`
  - `0004-helm-atomic-releases.md`
