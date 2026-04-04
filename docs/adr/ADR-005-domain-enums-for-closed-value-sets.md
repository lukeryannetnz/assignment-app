# ADR-005: Domain Enums For Closed Value Sets

- Status: Accepted
- Date: 2026-04-04

## Context

Some business concepts in this repository have a fixed, closed set of valid values. `org_nodes.node_type` is one example: only `company`, `business_unit`, `department`, and `team` are valid.

Representing these concepts as raw strings throughout controllers, models, services, DTOs, and tests has clear drawbacks:

- typos become runtime bugs
- literals are duplicated across the codebase
- validation and branching rules drift apart
- refactors are weaker because the valid set is not centralized

The database may continue storing these values as strings or enum columns, but the PHP domain model should not rely on scattered string literals for closed business value sets.

## Decision

Use PHP backed enums for closed domain value sets instead of passing raw strings through domain code.

## Rules

### 1. Represent closed business values as enums

If a domain concept has a fixed, known set of valid values, define a backed enum in the owning domain.

Example:

- `App\Domains\Tenancy\Data\OrgNodeType`

### 2. Keep database storage simple

The database may continue storing the enum-backed value as a string or database enum column. The PHP enum is the domain representation layered on top of that storage.

### 3. Use enums in domain code, not raw literals

Prefer enums in:

- model casts
- service logic
- DTOs and value objects
- validation rules when supported
- branching logic

Avoid scattering raw literals like `'company'` or `'team'` through business code.

### 4. Serialize enums only at boundaries

When values cross HTTP or persistence boundaries, convert the enum to its scalar value there.

Examples:

- SQL bindings use `$enum->value`
- JSON payloads expose `$enum->value`

### 5. Keep closed-value types with other domain data objects

Backed enums that model domain data belong alongside DTOs and value objects in the owning domain, for example:

- `domains/Tenancy/app/Data/OrgNodeType.php`

Do not create a separate `Enums` folder for business domain values. Do not place business enums in `domains/Foundation`.

## Consequences

### Positive

- closed value sets become explicit and discoverable
- typo risk is reduced
- validation, casting, and branching align on one source of truth
- refactors become safer

### Tradeoffs

- some conversion code is required at SQL and JSON boundaries
- enums can feel heavier than string constants for trivial one-off values

## Alternatives Considered

1. Keep raw strings everywhere
- Rejected because the domain invariant remains implicit and duplicated.

2. Use class constants instead of enums
- Rejected because enums provide stronger typing and better language support.

3. Use database enums only
- Rejected because storage constraints alone do not improve the PHP domain model.
