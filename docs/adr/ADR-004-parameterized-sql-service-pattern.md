# ADR-004: Prefer Parameterized SQL in Service Classes Over Eloquent ORM

- Status: Accepted
- Date: 2026-04-04

## Context

Course Manager is a domain-first Laravel application with explicit ownership boundaries under `domains/*`.

The repository already expects parameterized SQL and minimal framework leakage, but the current implementation is mixed:

- some controllers and services already use `DB::select`, `DB::update`, and `DB::table(...)`
- several domains still depend on Eloquent models, relationships, factories, and query builders
- authentication and test setup still assume Eloquent-backed user and factory behavior

This creates an inconsistent data access model. The current hybrid approach makes it harder to:

- reason about tenant isolation rules uniformly
- audit SQL for correctness and selected columns
- keep business logic inside explicit domain services
- remove accidental lazy-loading, relationship magic, and implicit ORM behavior

We want a single default approach that is explicit, testable, and aligned with the repository’s existing standards.

## Decision

Prefer parameterized SQL in service classes over Eloquent ORM.

New business logic must not use Eloquent models, Eloquent relationships, or query-builder chains rooted in Eloquent models. Domain read/write operations should be implemented in explicit service classes using parameterized SQL through Laravel’s database layer.

## Decision Summary

- Domain reads and writes belong in explicit service classes in the owning domain.
- Parameterized SQL through Laravel's database layer is the default persistence approach.
- Eloquent models and relationships are transitional framework adapters, not the target domain abstraction.
- Service contracts should return explicit data structures rather than active records.
- Queries should be explicit about the columns they select.

## Consequences

### Positive

- tenant-boundary rules are easier to inspect and enforce
- query behavior becomes explicit and predictable
- business logic can move into domain services without ORM coupling
- selected columns and write operations are easier to audit
- repository standards become internally consistent

### Tradeoffs

- more handwritten SQL and row-mapping code
- less convenience for simple CRUD flows
- authentication and password-reset integration need deliberate replacement strategy
- test factories and seeders need non-Eloquent alternatives

## Alternatives Considered

1. Keep the current hybrid model
- Rejected: preserves inconsistency and keeps domain boundaries partially implicit.

2. Standardize on Eloquent everywhere
- Rejected: conflicts with the repository’s explicit-SQL preference and weakens query/audit clarity.

3. Introduce a full repository abstraction layer before removing Eloquent
- Deferred: may be useful later, but a direct move to service-owned parameterized SQL is the simpler first step.
