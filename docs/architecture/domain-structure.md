# Domain Structure

## Goal

Keep Laravel as the framework shell while making business domains the primary organizing concern.

## Rules

- All business code lives under `app/Domain/<Domain>/...`.
- `app/Foundation/...` is reserved for Laravel composition only.
- Tests mirror domain ownership under `tests/Domain/<Domain>/...`.
- UI mirrors domain ownership under `resources/domains/<domain>/...`.
- Route definitions live in `app/Domain/<Domain>/Routes/web.php`.
- Database ownership is explicit under `database/factories/<Domain>`, `database/seeders/<Domain>`, and `database/migrations/<Domain>`.

## Domains

- `IdentityAccess`
- `CourseCatalog`
- `Enrollment`
- `Curriculum`
- `Tenancy`

## Allowed Foundation Code

The following may live under `app/Foundation` or `resources/foundation`:

- service providers
- route registrars
- view registrars
- framework-level assets
- vendor view overrides

Business logic does not belong in Foundation.

## Naming Conventions

### PHP namespaces

- `App\Domain\CourseCatalog\Http\Controllers\...`
- `App\Domain\IdentityAccess\Models\...`
- `App\Domain\Tenancy\Services\...`

### Views

- `course-catalog::dashboard`
- `course-catalog::admin.courses.index`
- `identity-access::auth.login`
- `curriculum::admin.sections.index`

### Blade components

- `<x-course-catalog::app-layout>`
- `<x-course-catalog::primary-button>`
- `<x-identity-access::guest-layout>`
- `<x-identity-access::input-label>`

### Routes

- `course-catalog.dashboard`
- `course-catalog.admin.courses.index`
- `identity-access.auth.login`
- `identity-access.admin.users.index`
- `curriculum.admin.sections.index`
- `tenancy.admin.org-nodes.index`

## Explicit Non-Rules

- There is no `Shared` domain.
- Generic business folders outside `app/Domain/*` are forbidden.
- Generic UI component folders outside domain folders are forbidden.
- Reuse through duplication is preferred over introducing catch-all shared abstractions.
