# ADR-005: Typed Domain Service Payloads

- Status: Accepted
- Date: 2026-04-04

## Context

Domain services in this repository often assemble structured results from parameterized SQL queries, validation logic, and workflow coordination. A common shortcut is to return nested associative arrays and describe the payload shape with PHPDoc array-shape annotations.

That pattern works, but it has clear drawbacks:

- contracts remain string-key based
- field names are duplicated across services, controllers, and tests
- refactors are brittle because shape changes are not centralized
- IDE support is weaker than for explicit classes
- array-shape comments can become a substitute for real types rather than documentation of them

This repository already uses explicit domain boundaries and ADR-driven architecture. Service result contracts should be explicit as well.

## Decision

Use typed DTOs or value objects for non-trivial domain service payloads instead of returning nested associative arrays documented only with PHPDoc array shapes.

## Decision Summary

- Non-trivial structured service results should be represented by explicit types in the owning domain.
- Arrays remain acceptable at boundaries such as validated request data, SQL bindings, and simple scalar collections.
- These payload types should stay simple, explicit, and easy to serialize when crossing HTTP boundaries.

## Consequences

### Positive

- service contracts become explicit
- refactors are safer because the payload shape is centralized
- service, controller, and test code share the same domain vocabulary
- PHPStan and IDE feedback improve

### Tradeoffs

- more files are required
- DTO mapping adds mechanical code
- very small internal services may feel heavier to implement

## Alternatives Considered

1. Keep associative arrays with PHPDoc shapes
- Rejected for non-trivial service payloads because the contracts remain stringly typed.

2. Return Eloquent models for everything
- Rejected because many services intentionally use parameterized SQL and return workflow-shaped payloads rather than aggregate models.

3. Use generic `stdClass` payloads
- Rejected because they remain weakly typed and do not materially improve contracts.
