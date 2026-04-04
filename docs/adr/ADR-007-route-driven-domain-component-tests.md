# ADR-007: Route-Driven Domain Component Tests

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

## Decision Summary

- Routed business workflows should be exercised through PHPUnit feature tests that enter through the HTTP boundary.
- These tests should verify persisted business outcomes, not only transport-level success.
- Parameterized SQL is the preferred setup and verification mechanism for this style of test.
- Lower-level tests still have value for infrastructure guarantees such as constraints, isolation, and architecture enforcement.

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
