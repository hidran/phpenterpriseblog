# 1. Composer + PSR-4 autoloading

Date: 2026-06-18

## Status

Accepted

## Context

The legacy `freeblog` project used a hand-rolled `require`-based autoloader and
had no dependency manager. Every new class meant another manual `require`, and
there was no standard way to pull in third-party libraries, run quality tools,
or share an autoload contract with the wider PHP ecosystem.

## Decision

Use Composer with PSR-4 autoloading. The namespace root `App\` maps to `src/`,
test code `Tests\` maps to `tests/`, and global helper functions are loaded via
Composer's `files` autoload. All dependencies (runtime and dev) are declared in
`composer.json` and locked in `composer.lock`.

## Consequences

- Every PHP ecosystem tool (PHPUnit, PHPStan, PHP_CodeSniffer, Rector) works out
  of the box because they all assume Composer + PSR-4.
- Adding a class is zero-ceremony: drop a correctly-namespaced file under `src/`.
- We accept a `vendor/` footprint and a dependency on Composer being present —
  an easy trade for losing the hand-maintained autoloader and gaining the
  ecosystem.
