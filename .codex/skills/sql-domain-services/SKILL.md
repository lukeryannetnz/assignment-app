---
name: sql-domain-services
description: Use when implementing or changing PHP backend domain logic, services, controllers, SQL queries, DTOs, or enums in this repository.
---

# SQL Domain Services Skill

Use this skill for backend Laravel work inside domain code.

## Canonical Decisions

Read these ADRs first when the change is non-trivial:

- `docs/adr/ADR-004-parameterized-sql-service-pattern.md`
- `docs/adr/ADR-005-typed-service-payloads.md`
- `docs/adr/ADR-006-domain-enums-for-closed-value-sets.md`

## Apply These Rules

- Put domain reads and writes in explicit service classes inside the owning domain. Keep controllers focused on request and response orchestration.
- Prefer parameterized SQL through Laravel's DB layer. Do not use Eloquent models, relationships, or model-rooted query chains as the default domain abstraction.
- Select only the columns the application needs.
- Use typed DTOs or value objects for non-trivial service payloads instead of nested associative arrays.
- Use PHP backed enums for closed business value sets and convert them to scalars only at HTTP or persistence boundaries.
- Add `declare(strict_types=1);` to every PHP file.
- Handle `$request->user()` as nullable.
- Validate method inputs up front, remove unused parameters, and remove unused `use` imports.
- Do not silence lint or static-analysis problems with ignore comments or weakened rules.
