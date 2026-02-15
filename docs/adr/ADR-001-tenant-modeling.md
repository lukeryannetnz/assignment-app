# ADR-001: Tenant Modeling and Isolation Boundaries

- Status: Accepted
- Date: 2026-02-15
- Related PRD: `docs/product/prd/PRD-001-enterprise-tenancy-org-hierarchy.md`

## Context

Course Manager must support enterprise multi-tenancy with strict data isolation, delegated organization hierarchy management, and auditable administrative changes. Existing functionality (course catalog, enrollment, curriculum, admin flows) was originally single-tenant.

The model needs to:

- Enforce hard tenant boundaries for all tenant-owned data.
- Support organization hierarchies with parent-child constraints.
- Preserve compatibility with current application workflows.
- Provide a clear foundation for future RBAC and enterprise controls.

## Decision

### 1. Introduce a first-class `tenants` aggregate root

A dedicated `tenants` table owns tenant metadata and lifecycle settings:

- `id` (immutable identity)
- `name`
- `status`
- `plan_tier`
- `hierarchy_depth_limit`

This table is the source of truth for tenancy context and hierarchy configuration.

### 2. Use explicit `tenant_id` foreign keys on tenant-owned entities

We model isolation by attaching `tenant_id` to all tenant-owned core entities and the enrollment pivot:

- `users`
- `courses`
- `sections`
- `curriculum_items`
- `quiz_questions`
- `course_user`

All read/write access for these entities is constrained by authenticated user tenant context.

### 3. Model organization structure in `org_nodes`

We represent tenant organization hierarchy in a single adjacency-list table:

- `tenant_id`
- `parent_id` (self-reference)
- `node_type` (`company`, `business_unit`, `department`, `team`)
- `name`
- `depth`
- `is_active`

Business rules are enforced in a tenancy domain service:

- max depth per tenant (`hierarchy_depth_limit`)
- cycle prevention on move
- no orphan-active behavior on deactivate/reactivate

### 4. Keep tenant scoping explicit at controller/service query boundaries

Tenant isolation is enforced via:

- request middleware (`EnsureTenantContext`) setting a per-request tenant context
- scoped queries in controllers/services using parameterized SQL/Eloquent constraints with `tenant_id`

We intentionally avoid global model scopes in this phase to keep isolation behavior explicit and testable in each flow.

### 5. Record high-impact tenant/org changes in `tenant_audit_logs`

We persist audit events with actor, action, and metadata:

- `tenant_created` (migration/backfill baseline)
- `tenant_updated`
- `org_node_created`
- `org_node_updated`
- `org_node_moved`
- `org_node_deactivated`

This provides a compliance-aligned trace for structural and tenant-level admin operations.

## ASCII ERD (Key Entities)

```text
+---------------------+
| tenants             |
+---------------------+
| PK id               |
| name                |
| status              |
| plan_tier           |
| hierarchy_depth_lim |
| created_at          |
| updated_at          |
+---------------------+
          | 1
          |
          | N
+----------------------+
| org_nodes            |
+----------------------+
| PK id                |
+ FK tenant_id         |
| FK parent_id -> self |
| node_type            |
| name                 |
| depth                |
| is_active            |
| created_at           |
| updated_at           |
+----------------------+
```

## Consequences

### Positive

- Tenant boundaries are explicit and enforceable across all current enterprise-critical flows.
- Organization hierarchy rules are centralized in a domain service.
- Isolation guarantees are testable with integration tests.
- Auditability exists for tenant and org-level structural operations.

### Tradeoffs

- Every tenant-owned query must include explicit `tenant_id` constraints; omissions are a risk without strong tests/reviews.
- Denormalized `tenant_id` on dependent tables and pivots increases write complexity but simplifies and hardens isolation checks.
- Adjacency-list hierarchy requires careful move/depth logic compared to simpler flat structures.

## Alternatives Considered

1. Flat group model (no hierarchy)
- Rejected: does not meet enterprise organizational scoping requirements.

2. Global Eloquent tenant scopes everywhere
- Deferred: less explicit behavior in complex admin/reporting flows; may be revisited after broader domain hardening.

3. Separate database/schema per tenant
- Rejected for current phase: higher operational complexity, migration overhead, and no immediate need for that isolation level.

## Follow-up

- Add scoped API contracts for assignment/reporting resolution.
- Expand audit metadata schema for compliance retention/search requirements.
- Evaluate optional global-safe tenant query helpers to reduce risk of missing constraints.
