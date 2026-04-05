# Course Manager

Course Manager is a Laravel 12 application for enterprise learning and workforce enablement. The platform is focused on helping organizations identify skill gaps, assign targeted learning, and track outcomes with governance and reporting built in.

![Course Manager screenshot](screenshot.png)

## Product Context

- Strategy: enterprise-first B2B SaaS for learning, skills, governance, and outcomes.
- Primary customers: mid-market and enterprise organizations with active reskilling programs.
- Primary users: L&D admins, HR business partners, managers, and learners.
- Current platform emphasis: multi-tenancy, organization hierarchy, RBAC foundation, course and curriculum management, enrollment workflows, skills intelligence foundation, and enterprise administration groundwork.

Read the product documents for the fuller picture:

- [Product strategy](docs/product/PRODUCT_STRATEGY.md)
- [12-month execution plan](docs/product/ENTERPRISE_12_MONTH_EXECUTION_PLAN.md)

The full PRD index lives in the documentation map below and under `docs/product/prd`.

## Architecture

The repository is organized domain-first. Laravel remains the application shell, while business-owned code lives under `domains/*`. Architectural decisions and implementation patterns are documented in the ADRs linked below.

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

This installs dependencies, prepares the environment, runs migrations, and builds frontend assets.

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
- Keep feature behavior covered with PHPUnit tests.

## Documentation Map

### Product

- [Product strategy](docs/product/PRODUCT_STRATEGY.md)
- [Enterprise 12-month execution plan](docs/product/ENTERPRISE_12_MONTH_EXECUTION_PLAN.md)
- [PRD-001: enterprise tenancy and organization hierarchy](docs/product/prd/PRD-001-enterprise-tenancy-org-hierarchy.md)
- [PRD-001 implementation plan](docs/product/prd/PRD-001-implementation-plan.md)
- [PRD-002: RBAC baseline](docs/product/prd/PRD-002-rbac-baseline.md)
- [PRD-003: role-to-skill mapping UI](docs/product/prd/PRD-003-role-to-skill-mapping-ui.md)
- [PRD-003 implementation plan](docs/product/prd/PRD-003-implementation-plan.md)
- [PRD-004: employee skill profile and gap scoring](docs/product/prd/PRD-004-employee-skill-profile-gap-scoring.md)
- [PRD-005: auto-suggested learning pathways by role](docs/product/prd/PRD-005-auto-suggested-learning-pathways-by-role.md)
- [PRD-006: manager assignment and progress tracking](docs/product/prd/PRD-006-manager-assignment-progress-tracking.md)

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

- The repository started as part of a Laravel training project and has since been reshaped into an enterprise product codebase.
- If you materially reshape domain ownership, update this README and the relevant ADR documentation in the same change.
- The `Skills` domain now anchors role-to-skill mapping UI scaffolding for the Pillar 1 rollout.
