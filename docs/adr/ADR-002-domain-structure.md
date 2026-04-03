# ADR-002: Domain-First Repository Structure

- Status: Accepted
- Date: 2026-04-04

## Context

Course Manager is a Laravel application that has grown across multiple business areas including identity and access, course catalog, curriculum, enrollment, and tenancy.

Without an explicit structure decision, Laravel-default folders encourage feature code, tests, UI assets, and database artifacts to drift into framework-oriented locations. That makes domain ownership harder to see, increases the chance of generic shared buckets appearing, and weakens consistency across application layers.

We need a repository structure that:

- keeps Laravel as the framework shell
- makes domain ownership explicit first
- mirrors domain boundaries across backend code, tests, UI, and database artifacts
- allows framework composition concerns to exist without becoming a business-logic catch-all

## Decision

### 1. Organize business code under top-level domains

All business code lives under `app/Domain/<Domain>/...`.

Current domains:

- `IdentityAccess`
- `CourseCatalog`
- `Enrollment`
- `Curriculum`
- `Tenancy`

### 2. Reserve `app/Foundation` for Laravel composition only

`app/Foundation/...` is the only non-domain location under `app/`.

It may contain framework composition concerns such as:

- service providers
- route registrars
- view registrars

Business logic does not belong in `app/Foundation`.

### 3. Mirror domain ownership across tests, UI, and database artifacts

The repository mirrors domain boundaries outside `app/`:

- tests live under `tests/Domain/<Domain>/...`
- UI lives under `resources/domains/<domain>/...`
- factories live under `database/factories/<Domain>/...`
- seeders live under `database/seeders/<Domain>/...`
- migrations live under `database/migrations/<Domain>/...`

Foundation-owned composition artifacts may also live in mirrored Foundation folders where Laravel needs them:

- `tests/Domain/Foundation/...`
- `resources/domains/foundation/...`
- `database/*/Foundation/...`

Laravel-required entrypoints may remain as thin framework wrappers when necessary, for example `database/seeders/DatabaseSeeder.php`.

### 4. Keep route composition explicit

`routes/web.php` remains composition-only.

Domain route definitions live in `app/Domain/<Domain>/Routes/web.php` and are composed from the framework shell.

### 5. Use domain-qualified naming conventions

Names must reflect domain ownership consistently:

- PHP namespaces mirror the filesystem, for example `App\Domain\CourseCatalog\Http\Controllers\...`
- views use namespaced references such as `course-catalog::admin.courses.index`
- Blade components use namespaced tags such as `<x-course-catalog::app-layout>`
- route names are domain-qualified such as `identity-access.auth.login` and `tenancy.admin.org-nodes.index`

### 6. Do not introduce generic shared domain buckets

The repository does not use `Shared` or similar catch-all domains.

Generic business folders outside `app/Domain/*` are forbidden. Generic UI component folders outside domain folders are forbidden. When presentation primitives are needed in more than one domain, duplication is preferred over introducing vague cross-domain abstractions.

## Consequences

### Positive

- Domain ownership is visible across code, tests, UI, and persistence.
- Laravel composition remains isolated from business logic.
- Structural drift is easier to detect and test.
- New features have a clear placement rule.

### Tradeoffs

- Some framework-owned files still require thin root entrypoints or registration layers.
- Reuse by duplication can increase maintenance overhead for UI primitives.
- Engineers must make an explicit domain decision before adding new files.

## Alternatives Considered

1. Laravel-default framework-first layout
- Rejected: hides business boundaries and encourages generic shared folders.

2. Shared or common cross-domain buckets
- Rejected: weakens ownership and becomes a dumping ground for unrelated concepts.

3. Pure domain folders with no Foundation exception
- Rejected: Laravel still benefits from a small framework-composition layer.
