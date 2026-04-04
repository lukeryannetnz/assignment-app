# ADR-006: Domain Enums For Closed Value Sets

- Status: Accepted
- Date: 2026-04-04

## Context

Some business concepts in this repository have a fixed, closed set of valid values. `org_nodes.node_type` is one example: only `company`, `business_unit`, `department`, and `team` are valid. `tenants.plan_tier` is another when the product supports a defined set of tenancy plans such as `enterprise_pilot` and `enterprise`.

Representing these concepts as raw strings throughout controllers, models, services, DTOs, and tests has clear drawbacks:

- typos become runtime bugs
- literals are duplicated across the codebase
- validation and branching rules drift apart
- refactors are weaker because the valid set is not centralized

The database may continue storing these values as strings or enum columns, but the PHP domain model should not rely on scattered string literals for closed business value sets.

## Decision

Use PHP backed enums for closed domain value sets instead of passing raw strings through domain code.

## Decision Summary

- Closed business value sets should be modeled as backed enums in the owning domain.
- Database storage may remain scalar; the enum is the PHP domain representation layered over that storage.
- Domain logic should use enums rather than scattered string literals, converting to scalars only at persistence or HTTP boundaries.

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
