# ADR-006: Route-Driven Domain Component Tests

- Status: Accepted
- Date: 2026-04-04

## Context

This repository already favors PHPUnit feature tests, explicit domain boundaries, and parameterized SQL over hidden ORM behavior. Some recent domain tests have still bypassed the application surface by instantiating controllers directly or calling services in isolation for workflows that are primarily consumed through HTTP routes.

That style verifies logic, but it misses important integration behavior:

- route middleware and authorization
- request validation
- tenant context setup
- controller serialization
- database side effects across the full workflow boundary

For domain workflows such as tenancy administration, the more useful test unit is often a domain slice exercised end to end through its HTTP entrypoint.

## Decision

For business workflows exposed through domain routes, prefer route-driven component tests that exercise the full domain slice end to end and assert persisted outcomes in the database.

## Rules

### 1. Treat component tests as feature tests

Component tests in this repository are PHPUnit feature tests that run through the Laravel HTTP stack.

They belong in the owning domain test boundary, for example:

- `domains/Tenancy/tests/Feature/...`

### 2. Enter through the public workflow boundary

When a business capability is exposed through a route, test it through that route instead of directly invoking:

- controllers
- domain services
- request objects assembled by hand

This keeps middleware, validation, authorization, tenant context, and serialization inside the tested path.

### 3. Assert business outcomes, not just response codes

Component tests should verify:

- response status and payload shape where relevant
- created or updated database rows
- audit log side effects
- emitted domain events when they are part of the workflow contract

Avoid tests that stop at `200 OK` without checking the persisted outcome.

### 4. Prefer parameterized SQL for fixture setup and verification

When preparing test data or verifying persisted state in component tests, prefer parameterized inline SQL through `DB::insert`, `DB::selectOne`, `DB::select`, `DB::update`, and `DB::delete`.

Avoid relying on:

- Eloquent factories for primary fixture setup
- Eloquent query APIs for primary verification
- assertion helpers that hide the underlying persisted shape

This keeps component tests aligned with the repository's explicit-SQL approach and makes the data contract under test visible in the test itself.

### 5. Cover a domain slice, not isolated methods

A component test should describe a user-visible behavior or domain workflow, for example:

- provisioning a tenant shell
- updating tenant settings
- creating, moving, deactivating, and reactivating org nodes
- resolving an org scope for reporting or assignments

### 6. Keep lower-level tests when they protect infrastructure guarantees

This ADR does not replace lower-level tests that verify:

- database constraints
- tenancy isolation guarantees
- architecture rules

Those tests still provide value and may remain outside the route-driven component style.

## Consequences

### Positive

- business behavior is verified through the same entrypoints production uses
- authorization, validation, and tenant middleware are covered together
- tests describe user-visible workflows more clearly
- regression risk moves closer to real runtime behavior

### Tradeoffs

- tests can be slower than direct service tests
- setup is slightly heavier because HTTP context is required
- failures can span more layers and require broader debugging

## Alternatives Considered

1. Continue using direct controller or service tests for routed workflows
- Rejected because they bypass important integration behavior and tenant middleware setup.

2. Convert all tests to full browser tests
- Rejected because browser coverage is heavier than needed for most backend domain workflows.

3. Rely only on unit tests plus a few smoke tests
- Rejected because the tenancy workflows depend on integration concerns such as middleware, validation, and persisted side effects.
