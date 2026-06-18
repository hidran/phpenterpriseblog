# 3. Forward-only migrations

Date: 2026-06-18

## Status

Accepted

## Context

Database migrations can be reversible (up/down) or forward-only. Reversible
"down" migrations are seductive but are rarely correct under load: a `down` that
drops a column destroys data, and in production you almost never roll a schema
*backwards* — you roll *forward* with a compensating migration.

## Decision

Migrations are **append-only and forward-only**. The runner (`bin/console
migrate`) applies pending `*.sql` files in lexical order and records them in a
`migrations` tracking table; there is no `down`. A change that replaces a column
must not drop the old one in the same release that introduces the replacement —
the drop is a later, separate migration once nothing reads the old column.

## Consequences

- Rollback safety: deploying an older app version never silently destroys data,
  because the schema only ever moved forward in compatible steps.
- Releases and migrations decouple cleanly — the Helm `--atomic` release (see
  ADR 0004) can roll the *app* back without the *schema* having been destroyed.
- The cost is schema churn: removing a column takes two releases (stop reading,
  then drop). Accepted — rollback safety outweighs convenience.
