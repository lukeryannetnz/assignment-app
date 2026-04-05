# Course Manager

Course Manager is a Laravel 12 application evolving into an enterprise-first learning platform for workforce enablement. The product direction is to help mid-market and enterprise organizations identify skill gaps, assign targeted learning, and prove workforce impact with governance and reporting built in.

This repository is organized domain-first. Laravel remains the framework shell, while business-owned code lives under explicit top-level domains in `domains/*`.

## Product Context

- Strategy: enterprise-first B2B SaaS for learning, skills, governance, and outcomes.
- Primary customers: mid-market and enterprise organizations with active reskilling programs.
- Primary users: L&D admins, HR business partners, managers, and learners.
- Current platform emphasis: multi-tenancy, organization hierarchy, RBAC foundation, course and curriculum management, enrollment workflows, and enterprise administration groundwork.

Read the product documents for the fuller picture:

- [Product strategy](docs/product/PRODUCT_STRATEGY.md)
- [12-month execution plan](docs/product/ENTERPRISE_12_MONTH_EXECUTION_PLAN.md)
- [PRD-001: enterprise tenancy and org hierarchy](docs/product/prd/PRD-001-enterprise-tenancy-org-hierarchy.md)
- [PRD-001 implementation plan](docs/product/prd/PRD-001-implementation-plan.md)
- [PRD-002: RBAC baseline](docs/product/prd/PRD-002-rbac-baseline.md)

## Repository Structure

The repository follows the accepted root-level domain structure from [ADR-003](docs/adr/ADR-003-root-domain-structure.md).

```text
domains/
  CourseCatalog/
    app/
    resources/
    database/
    tests/
  Curriculum/
    app/
    resources/
    database/
    tests/
  Enrollment/
    app/
    database/
    tests/
  Foundation/
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
```

### Domain Ownership

- `CourseCatalog`: course browsing, dashboards, and admin course management.
- `Curriculum`: sections, curriculum items, and quiz questions.
- `Enrollment`: enroll and unenroll workflows.
- `IdentityAccess`: authentication, registration, verification, password flows, profile management, and user administration.
- `Tenancy`: tenant context, tenant provisioning foundations, org hierarchy management, tenant-safe scope resolution, audit review, and isolation rules.
- `Foundation`: Laravel composition only. Providers, route registration, framework assets, view wiring, vendor overrides, and architecture tests belong here. Business logic does not.

### Working Rules

- Keep business code inside its owning domain.
- Do not create generic shared business folders outside `domains/*`.
- Do not use `Foundation` as a business-logic catch-all.
- Duplicate presentation primitives across domains when ownership is clearer than sharing.

## Architecture Notes

### Routing

- Domain routes live in `domains/<Domain>/app/Routes/web.php`.
- `routes/web.php` is composition-only and delegates to domain route registration.
- Route names are domain-qualified, for example:
  - `course-catalog.dashboard`
  - `curriculum.admin.sections.index`
  - `identity-access.auth.login`
  - `tenancy.admin.org-nodes.index`

### Views and Blade Components

- Domain views live in `domains/<Domain>/resources/views`.
- Domain anonymous Blade components live in `domains/<Domain>/resources/components`.
- Foundation-owned framework assets and vendor overrides live in `domains/Foundation/resources/...`.
- View and component names are domain-namespaced.

### Tenancy Scope Contracts

- `GET /admin/tenancy/org-nodes/{id}/scope` returns the requested node plus ancestors, descendant subtree, and descendant IDs.
- `GET /admin/tenancy/org-nodes/{id}/scopes/company` resolves the nearest company boundary and returns that subtree contract.
- `GET /admin/tenancy/org-nodes/{id}/scopes/department` resolves the nearest department boundary and returns that subtree contract.
- `GET /admin/tenancy/org-nodes/{id}/scopes/team` resolves the nearest team boundary and returns that subtree contract.
- Cross-tenant references are rejected.

### Architectural Decisions

- [ADR-001: tenant modeling and isolation boundaries](docs/adr/ADR-001-tenant-modeling.md)
- [ADR-003: root-level domain structure](docs/adr/ADR-003-root-domain-structure.md)
- [ADR-004: parameterized SQL service pattern](docs/adr/ADR-004-parameterized-sql-service-pattern.md)
- [ADR-005: typed domain service payloads](docs/adr/ADR-005-typed-service-payloads.md)
- [ADR-006: domain enums for closed value sets](docs/adr/ADR-006-domain-enums-for-closed-value-sets.md)
- [ADR-007: route-driven domain component tests](docs/adr/ADR-007-route-driven-domain-component-tests.md)

## Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- Docker / Docker Compose
- MariaDB

## Getting Started

### 1. Install dependencies and bootstrap the app

```bash
composer setup
```

This script:

- installs PHP dependencies
- copies `.env.example` to `.env` when needed
- generates the application key
- runs database migrations
- installs frontend dependencies
- builds frontend assets

### 2. Start MariaDB

```bash
docker-compose up -d
```

The default local database settings in `.env.example` target the MariaDB container defined in `docker-compose.yml`.

### 3. Start the development stack

```bash
composer dev
```

This starts the Laravel server, queue worker, Pail log viewer, and Vite dev server together.

## Developer Workflow

### Required checks

Before finishing code changes, run:

```bash
composer test
```

`composer test` runs:

1. `phpcs`
2. `phpstan`
3. `php artisan config:clear`
4. `php artisan test`

You can run the quality tools individually when iterating:

```bash
composer lint
composer phpcs
composer phpstan
composer lint:fix
```

More detail lives in [LINTING.md](LINTING.md).

### PHP guidelines

- Use `declare(strict_types=1);` in PHP files.
- Prefer simple, explicit code and typed contracts.
- Follow the repository ADRs before introducing new patterns.
- Keep feature behavior covered with PHPUnit tests, especially route-driven domain tests for HTTP workflows.

## Documentation Map

### Product

- [Product strategy](docs/product/PRODUCT_STRATEGY.md)
- [Enterprise 12-month execution plan](docs/product/ENTERPRISE_12_MONTH_EXECUTION_PLAN.md)
- [PRD-001: enterprise tenancy and organization hierarchy](docs/product/prd/PRD-001-enterprise-tenancy-org-hierarchy.md)
- [PRD-001 implementation plan](docs/product/prd/PRD-001-implementation-plan.md)
- [PRD-002: RBAC baseline](docs/product/prd/PRD-002-rbac-baseline.md)

### Architecture

- [ADR-001: tenant modeling and isolation boundaries](docs/adr/ADR-001-tenant-modeling.md)
- [ADR-002: domain-first repository structure (superseded)](docs/adr/ADR-002-domain-structure.md)
- [ADR-003: root-level domain structure](docs/adr/ADR-003-root-domain-structure.md)
- [ADR-004: parameterized SQL service pattern](docs/adr/ADR-004-parameterized-sql-service-pattern.md)
- [ADR-005: typed domain service payloads](docs/adr/ADR-005-typed-service-payloads.md)
- [ADR-006: domain enums for closed value sets](docs/adr/ADR-006-domain-enums-for-closed-value-sets.md)
- [ADR-007: route-driven domain component tests](docs/adr/ADR-007-route-driven-domain-component-tests.md)

### Security and Compliance

- [Tenant audit and compliance checklist](docs/security/tenant-audit-compliance-checklist.md)

## Notes

- The repository started as part of a Laravel training project and has since been reshaped into a domain-first enterprise product codebase.
- If you add a new domain or materially reshape domain ownership, update this README and the relevant ADR documentation in the same change.
