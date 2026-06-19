# phpenterpriseblog

PHP 8.5 enterprise-shaped remake of the Udemy `freeblog` MVC course project.

## Quickstart
```bash
composer install   # required first: the app container bind-mounts the repo and
                   # opcache.preload needs vendor/ (without it php-fpm exits 70)
make up            # docker compose up + migrate
make seed          # optional: load demo data
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
| `seed` | load demo data |
| `e2e` | Playwright specs |

## Releases
Tag `v*` on `main` → GitHub Actions builds + deploys staging → manual approval → prod.

See `docs/superpowers/specs/2026-06-17-php85-modernization-design.md` for the full design and `docs/adr/` for decision records.
