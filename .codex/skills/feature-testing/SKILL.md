---
name: feature-testing
description: Use when changing application behavior, writing or updating PHPUnit tests, or verifying completion for code changes in this repository.
---

# Feature Testing Skill

Use this skill whenever behavior changes or tests need to be added or updated.

## Canonical Decision

Read `docs/adr/ADR-007-route-driven-domain-component-tests.md` first for routed workflow testing.

## Apply These Rules

- Prefer PHPUnit feature tests in the owning domain test boundary.
- For workflows exposed through HTTP routes, test through the route instead of directly invoking controllers or services.
- Assert persisted outcomes and other workflow effects, not only response codes.
- Prefer parameterized inline SQL for fixture setup and persistence verification over Eloquent factories or ORM-style assertions.
- Update tests when routes or behavior change.
- If you touch factories, ensure required fields have defaults.

## Completion Gate

- Run `composer test` before considering the task complete.
- `composer test` must pass with linting and tests green.
