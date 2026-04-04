# Course Management Application

A Laravel-based course management system organized around top-level business domains.

## Context

This application was created as part of the Coursera **Master Full-Stack Web Development with Laravel & PHP** course.

## Architecture

Laravel remains the application framework and runtime shell. Business code is organized domain-first.

The current repository-structure decision is documented in [ADR-003: Root-Level Domain Structure](docs/adr/ADR-003-root-domain-structure.md).

### Canonical Structure

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
    resources/
    database/
    tests/
```

### Domain Responsibilities

- `IdentityAccess`: authentication, registration, email verification, password flows, profile management, and user administration.
- `CourseCatalog`: course browsing, dashboarding, and admin course management.
- `Enrollment`: enroll and unenroll workflows.
- `Curriculum`: sections, curriculum items, and quiz questions.
- `Tenancy`: tenant context, tenant provisioning, tenancy admin UI, org hierarchy management/import, and tenant isolation rules.
- `Foundation`: Laravel composition only. Providers, route registration, view namespace registration, framework-level assets, vendor view overrides, and architecture tests live here. No business logic belongs here.

### Routing

- Domain routes live in `domains/<Domain>/app/Routes/web.php`.
- `routes/web.php` is composition-only and delegates to the domain route registrar.
- Route names are domain-qualified, for example:
  - `course-catalog.dashboard`
  - `course-catalog.admin.courses.index`
  - `curriculum.admin.sections.index`
  - `identity-access.auth.login`
  - `identity-access.admin.users.index`
  - `tenancy.admin.org-nodes.index`

### Views and Blade Components

- Domain views live in `domains/<Domain>/resources/views`.
- Domain anonymous components live in `domains/<Domain>/resources/components`.
- Foundation-owned framework assets and vendor view overrides live in `domains/Foundation/resources/...`.
- View names are namespaced:
  - `course-catalog::dashboard`
  - `course-catalog::admin.courses.index`
  - `identity-access::auth.login`
  - `curriculum::admin.sections.index`
- Blade anonymous components are namespaced:
  - `<x-course-catalog::app-layout>`
  - `<x-course-catalog::graduation-cap-logo>`
  - `<x-identity-access::guest-layout>`
  - `<x-identity-access::input-label>`

### Shared Code Policy

- There is no `Shared` domain.
- Do not create generic business folders outside `domains/*`.
- Do not create generic UI component folders outside domain folders.
- If presentation primitives are needed in more than one domain, duplicate them into each owning domain.

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js and npm
- Docker and Docker Compose (for MariaDB)
- MariaDB

## Installation

```bash
composer setup
```

This will:

- Install PHP dependencies
- Copy `.env.example` to `.env`
- Generate application key
- Run database migrations
- Install npm packages
- Build frontend assets

## Development

### Starting the Database

```bash
docker-compose up -d
```

### Starting Development Servers

```bash
composer dev
```

This runs Laravel's development server, queue worker, logs viewer (Pail), and Vite concurrently.

## Testing

Run the full test suite with linting:

```bash
composer test
```

This command:

1. Runs PHP_CodeSniffer for code style validation
2. Runs PHPStan for static analysis
3. Clears configuration cache
4. Executes PHPUnit tests

## Coding Standards

All PHP files must include:

```php
<?php

declare(strict_types=1);
```

The project uses PSR-12, PHPStan level 9, and PHPUnit feature tests.
