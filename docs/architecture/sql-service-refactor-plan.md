# SQL Service Refactor Plan

## Goal

Remove Eloquent ORM usage from domain business logic and move all domain data access to parameterized SQL in explicit service classes.

## Current State Summary

The codebase currently uses three data-access patterns:

1. Parameterized SQL services
- Already present in parts of `Tenancy` and some controller logic.

2. Eloquent-backed domain models and relationships
- Present across `CourseCatalog`, `Curriculum`, `IdentityAccess`, and `Tenancy`.

3. Eloquent factories and model-centric tests
- Present in most domain tests and seeders.

## Refactor Strategy

Use a staged domain-by-domain migration rather than a single large rewrite.

Sequence matters:

1. Introduce service interfaces and row-shape/DTO conventions.
2. Move controller query logic into services.
3. Replace model usage in business flows.
4. Replace Eloquent-backed tests and factories.
5. Remove Eloquent compatibility code last, after auth and test infrastructure are replaced.

## Cross-Cutting Work First

### 1. Define a standard service pattern

Create a consistent domain service approach for:

- reads
- writes
- row mapping
- transaction boundaries
- not-found handling
- validation failures

Recommended conventions:

- SQL services return typed arrays or DTOs
- service methods accept scalar IDs and validated payloads
- services own transaction boundaries for multi-write flows

### 2. Introduce shared test helpers for SQL-backed setup

Replace Eloquent-heavy factory usage with explicit test builders or helper services.

Needed helpers:

- tenant builder
- user builder
- course builder
- section builder
- curriculum item builder
- quiz question builder
- org node builder

These can use parameterized inserts and return inserted IDs/rows.

### 3. Plan authentication replacement before deleting the `User` model

`IdentityAccess` is the critical path because Laravel auth currently uses an Eloquent provider in `config/auth.php`.

Fastest safe target:

- switch from `eloquent` to a custom or database-backed provider
- introduce a thin authenticatable adapter for framework auth concerns
- move all business reads and writes for users/password flows into SQL services

Do not try to remove auth integration and all user-related business logic in the same uncontrolled pass.

## Domain-by-Domain Plan

### CourseCatalog

Current issues:

- `Course` is an Eloquent model with relationships
- controllers still rely on the model for persistence and related lookups

Refactor steps:

1. Add `CourseCatalogService` for:
- list courses by tenant/user
- fetch course detail by tenant
- create course
- update course
- delete course
- compute enrollment counts

2. Move admin and student controller queries into the service.

3. Replace any enrollment-related relationship access with SQL joins.

4. Delete the `Course` model after tests no longer depend on it.

### Curriculum

Current issues:

- `Section`, `CurriculumItem`, and `QuizQuestion` are Eloquent models
- controllers still rely on model persistence and model-based relations

Refactor steps:

1. Add `CurriculumService` or separate focused services for:
- section CRUD
- curriculum item CRUD
- quiz question CRUD
- quiz duration calculation
- ordered subtree reads

2. Replace relationship-based cascades with explicit SQL deletes in transactions.

3. Replace model-based ordering and tenant lookup logic with explicit SQL joins.

4. Delete all curriculum models after tests and seeders stop referencing them.

### Enrollment

Current issues:

- controller logic interacts with Eloquent-backed user/course assumptions
- pivot behavior still assumes model relationships

Refactor steps:

1. Add `EnrollmentService` for:
- enroll
- unenroll
- enrollment existence check
- enrollment counts and list state

2. Replace relation-based checks with explicit SQL against `course_user`.

3. Update tests to assert SQL-backed side effects directly.

This domain should be one of the fastest to complete.

### IdentityAccess

Current issues:

- `User` is an Eloquent-authenticatable model
- auth, password reset, and profile flows depend on framework integration points
- tests and factories are heavily model-based

Refactor steps:

1. Introduce SQL-backed services for:
- user lookup
- login credential lookup
- user registration
- profile update
- admin promotion/demotion
- password update and reset support

2. Replace controller business logic first while keeping a temporary auth adapter.

3. Migrate auth provider configuration away from Eloquent.

4. Replace Eloquent factories with SQL test builders.

5. Remove the Eloquent `User` model last.

This is the hardest domain and should be sequenced after service patterns are proven elsewhere.

### Tenancy

Current issues:

- `OrganizationHierarchyService` already uses parameterized SQL
- `Tenant`, `OrgNode`, and `TenantAuditLog` models still exist
- tests and factories still use model-backed setup

Refactor steps:

1. Keep `OrganizationHierarchyService` as the template.

2. Add or complete SQL services for:
- tenant provisioning/update
- tenant audit lookup
- org node reads for admin UI/API

3. Replace model usage in middleware, tests, and setup helpers.

4. Remove tenancy models after factories/tests are converted.

This domain is the best place to establish the final pattern because it is already closest.

### Foundation

Current issues:

- seeders and test infrastructure still assume Eloquent factories/models

Refactor steps:

1. Replace `ApplicationSeeder` logic with SQL-backed seed services or direct parameterized inserts.

2. Replace base test setup helpers to stop depending on model factories.

3. Update architecture guard tests later to fail on new Eloquent usage patterns.

## Recommended Execution Order

1. Tenancy
- already closest to the target pattern

2. CourseCatalog
- moderate complexity, strong user-visible coverage

3. Enrollment
- small surface area, dependent on course/user service contracts

4. Curriculum
- more write paths and cascade logic

5. Foundation test/seed infrastructure
- unlocks full Eloquent removal

6. IdentityAccess
- highest framework coupling, finish last

## Completion Gates

For each domain, do not mark the refactor complete until:

- controller/business logic uses SQL services only
- no domain model query or persistence APIs remain
- tests for that domain no longer rely on Eloquent factories
- `composer test` passes

For the full repository, completion means:

- no `extends Model` usage in domain business code
- no `HasFactory`
- no relationship methods
- no `Model::query()`
- auth no longer depends on an Eloquent provider

## Known Hard Problems

- Laravel session auth and password reset currently expect an authenticatable user abstraction
- existing tests rely on model factories for concise setup
- some seeded data currently uses factories and model creation chains

These should be treated as first-class migration tasks, not cleanup afterthoughts.
