# 4. Atomic Helm releases

Date: 2026-06-18

## Status

Accepted

## Context

A Kubernetes deploy has several moving parts — a pre-upgrade migration Job, new
pods rolling in, health checks. If any step fails partway, a naive `helm
upgrade` can leave the release in a broken, half-applied state that still serves
traffic.

## Decision

Every `helm upgrade --install` in the release pipeline uses `--wait --atomic`
(with a timeout). `--wait` blocks until the new pods are healthy; `--atomic`
automatically rolls the whole release back to the previous revision if the
upgrade (including the pre-upgrade migrate hook) fails. Production additionally
sits behind a GitHub Environment approval gate and is only reached after the
staging smoke test passes.

## Consequences

- No partial releases: a failed migration or unhealthy rollout reverts cleanly
  rather than leaving a broken deployment live.
- Combined with forward-only migrations (ADR 0003), an app rollback is safe
  because the schema was never destructively changed.
- Deploys are slower (Helm waits for health) and a release can fail outright
  rather than limping — both are desirable: fail loudly and stay consistent.
