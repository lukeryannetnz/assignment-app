# ADR-003: Root-Level Domain Structure

- Status: Accepted
- Date: 2026-04-04
- Supersedes: `docs/adr/ADR-002-domain-structure.md`

## Context

ADR-002 established a domain-first repository structure while keeping Laravel's default top-level folders (`app`, `resources`, `database`, `tests`) as the primary filesystem anchors.

That decision improved domain ownership substantially, but the repository is still organized framework-first at the root. A feature spans multiple root folders:

- `domains/<Domain>/...`
- `resources/domains/<domain>/...`
- `tests/Domain/<Domain>/...`
- `database/<type>/<Domain>/...`

This means a single domain still requires navigation across several top-level Laravel directories. If we want domains to become the primary organizational unit of the repository rather than just the primary namespace inside Laravel folders, the root structure must change as well.

## Decision

Adopt a root-level domain structure where each business domain owns its application code, tests, UI, routes, and persistence artifacts from a single top-level location.

Laravel remains the runtime shell, but the repository is organized domain-first at the root.

## Target Structure

```text
domains/
  CourseCatalog/
    app/
      Http/
      Models/
      Routes/
    resources/
      views/
      components/
    database/
      factories/
      migrations/
      seeders/
    tests/
      Feature/
  Curriculum/
    app/
    resources/
    database/
    tests/
  Enrollment/
    app/
    resources/
    database/
    tests/
  IdentityAccess/
    app/
    resources/
    database/
    tests/
  Tenancy/
    app/
    resources/
    database/
    tests/
  Foundation/
    app/
      Providers/
      Routing/
      Views/
    resources/
      css/
      js/
      views/
    database/
      migrations/
      seeders/
    tests/
      Architecture/

bootstrap/
config/
public/
routes/
storage/
vendor/
```

## Detailed Rules

### 1. Domains become the top-level ownership boundary

All domain-owned artifacts live under `domains/<Domain>/...`.

Each domain owns:

- PHP application code under `domains/<Domain>/app/...`
- Blade views and UI assets under `domains/<Domain>/resources/...`
- tests under `domains/<Domain>/tests/...`
- factories, migrations, and seeders under `domains/<Domain>/database/...`

### 2. Foundation remains the only non-business domain

`domains/Foundation/...` owns Laravel composition concerns and framework assets.

It may contain:

- service providers
- route registrars
- view registrars
- shared runtime assets required by the framework shell
- vendor view overrides
- architecture tests that enforce repository structure

Business logic does not belong in Foundation.

### 3. Laravel entrypoints stay thin and compositional

Laravel-required root files may remain at their conventional paths where the framework expects them, but they must be thin wrappers only.

Examples:

- `bootstrap/app.php`
- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`
- Vite and PHPUnit root configuration files

These files compose or delegate into the domain tree and must not become business-logic containers.

### 4. Naming conventions continue to be domain-qualified

- PHP namespaces mirror the domain filesystem
- view namespaces remain domain-qualified
- Blade component prefixes remain domain-qualified
- route names remain domain-qualified

Example namespace mapping:

- `domains/CourseCatalog/app/Http/Controllers/...` maps to `App\Domains\CourseCatalog\Http\Controllers\...`

### 5. Shared buckets remain forbidden

Do not introduce `Shared`, `Common`, or similar catch-all business domains.

If reuse is needed:

- prefer keeping behavior inside the owning domain
- duplicate presentation primitives when that is cheaper than a vague cross-domain abstraction
- place true framework composition in `domains/Foundation`

## Migration Plan For This Repository

### Phase 1: Autoload and namespace shift

- change Composer PSR-4 mappings from `App\\ => app/` to `App\\Domains\\ => domains/`
- map database factories and seeders to `domains/*/database/...` via explicit autoload configuration or custom discovery
- update PHPStan and PHPUnit source/include paths

### Phase 2: Move domain code

- move `app/Domain/CourseCatalog/...` to `domains/CourseCatalog/app/...`
- move equivalent structures for `Curriculum`, `Enrollment`, `IdentityAccess`, and `Tenancy`
- move `app/Foundation/...` to `domains/Foundation/app/...`
- update namespaces and imports

### Phase 3: Move UI and database artifacts

- move `resources/domains/<domain>/...` to `domains/<Domain>/resources/...`
- move `database/factories/<Domain>/...` to `domains/<Domain>/database/factories/...`
- move `database/migrations/<Domain>/...` to `domains/<Domain>/database/migrations/...`
- move `database/seeders/<Domain>/...` to `domains/<Domain>/database/seeders/...`

### Phase 4: Move tests

- move `tests/Domain/<Domain>/...` to `domains/<Domain>/tests/...`
- move `tests/Domain/Foundation/...` to `domains/Foundation/tests/...`
- update PHPUnit suite definitions and base test namespaces

### Phase 5: Rebuild runtime composition

- replace current domain registrars with discovery based on `domains/*`
- update Blade namespace registration
- update migration loading
- update route registration
- update Vite inputs and asset references
- preserve thin root Laravel entrypoints

## Consequences

### Positive

- each domain becomes a self-contained unit at the repository root
- feature navigation becomes simpler because code, tests, UI, and persistence live together
- domains are easier to extract into packages or bounded modules later

### Tradeoffs

- Laravel-default conventions no longer match the repository layout
- more custom composition code is required for autoloading, Blade registration, migrations, tests, and assets
- refactoring risk is materially higher than the current ADR-002 layout
- onboarding is less familiar for Laravel developers expecting conventional roots

## Difficulty Assessment

For this repository, this change is not a light reorganization. It is a moderate-to-large architectural refactor.

Expected difficulty:

- conceptually: straightforward
- mechanically: difficult
- operational risk: medium-high

The highest-risk areas are:

- Composer namespace and autoload migration
- factory and seeder discovery
- migration loading and execution
- Blade namespace registration
- PHPUnit configuration and test base classes
- Vite asset discovery and template references

## Alternatives Considered

1. Keep ADR-002 as-is
- Lowest-risk option and still strongly domain-oriented, but domains remain split across Laravel root folders.

2. Introduce a `src/Domains` subtree only for PHP code
- Simpler than full root-domain ownership, but still leaves tests, UI, and database artifacts split across the repository.

3. Convert each domain into a package
- Strong modularity, but too heavy for the current application phase and would add more packaging overhead than value right now.
