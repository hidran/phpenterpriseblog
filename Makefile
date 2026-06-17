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
