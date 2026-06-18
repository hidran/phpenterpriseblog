# 2. Models as DTOs, SQL in Repositories

Date: 2026-06-18

## Status

Accepted

## Context

The legacy code mixed SQL, request handling, and data shaping inside fat "model"
classes. That made the data layer impossible to unit-test without a database and
blurred the line between "what a Post is" and "how a Post is fetched".

## Decision

Split the two concerns:

- **Models** (`src/Models/`) are immutable, typed data objects (DTOs) — `final
  readonly` value classes with a `fromRow()` factory. They contain **no SQL**.
- **Repositories** (`src/Repositories/`) own all SQL. They take a `PDO`, run
  queries, and hydrate Models via `fromRow()`.

## Consequences

- Repositories can be tested with a mocked/sqlite `PDO`; Models are trivial to
  construct in tests.
- A clear seam exists for decoration (e.g. `CachedPostRepository`) and for
  programming against interfaces (e.g. `UserRepositoryInterface`).
- More files and a little more ceremony than one fat class — accepted in
  exchange for testability and a single responsibility per class.
- Enforced mechanically: a self-review check asserts no file in `src/Models/`
  contains SQL.
