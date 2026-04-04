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

## Detailed Rules

### 1. Domain data access belongs in services

Database reads and writes should live in explicit domain service classes or narrowly-scoped data access services under the owning domain.

Examples:

- `domains/CourseCatalog/app/Services/CourseCatalogService.php`
- `domains/IdentityAccess/app/Services/UserAuthenticationService.php`
- `domains/Tenancy/app/Services/OrganizationHierarchyService.php`

Controllers should orchestrate requests and responses only. They should not assemble significant SQL inline unless the query is trivial and already delegated through a dedicated domain service.

### 2. Use parameterized SQL for reads and writes

Queries must use placeholders and bound parameters.

Preferred mechanisms:

- `DB::select(...)`
- `DB::selectOne(...)`
- `DB::insert(...)`
- `DB::update(...)`
- `DB::delete(...)`
- `DB::transaction(...)`

`DB::table(...)` is allowed only as a transitional mechanism and should be reduced over time where raw parameterized SQL is clearer.

### 3. Do not use Eloquent as the default domain abstraction

Avoid:

- `Model::query()`
- `Model::create()`
- relationship methods such as `belongsTo`, `hasMany`, and `belongsToMany`
- `HasFactory`
- model attribute casting as a business-logic dependency
- implicit persistence through `save()`, `update()`, `delete()`

If framework compatibility requires a temporary Eloquent-backed class, it should be treated as an adapter to be removed later, not as the core domain pattern.

### 4. Return explicit arrays or DTOs, not active records

Services should return:

- arrays with documented shapes
- small immutable DTOs/value objects
- scalar values for simple lookups

They should not return Eloquent model instances or relationship collections.

### 5. Select only the columns the application needs

Queries should be explicit about selected columns. Avoid `SELECT *` unless there is a clear justification and no narrower projection is practical.

### 6. Keep framework integration explicit

Where Laravel requires authentication, password reset, notifications, or similar framework contracts, introduce thin integration adapters rather than allowing Eloquent models to spread through business logic.

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

## Exceptions

The following are temporary exceptions only while the refactor is in progress:

- Eloquent-backed authentication model and provider integration in `IdentityAccess`
- Eloquent factories used by existing tests
- framework-driven compatibility code that cannot be removed in the same slice

These exceptions should be documented in the refactor plan and reduced domain by domain.

## Alternatives Considered

1. Keep the current hybrid model
- Rejected: preserves inconsistency and keeps domain boundaries partially implicit.

2. Standardize on Eloquent everywhere
- Rejected: conflicts with the repository’s explicit-SQL preference and weakens query/audit clarity.

3. Introduce a full repository abstraction layer before removing Eloquent
- Deferred: may be useful later, but a direct move to service-owned parameterized SQL is the simpler first step.
