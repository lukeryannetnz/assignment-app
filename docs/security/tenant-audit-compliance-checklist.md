# Tenant Audit Compliance Checklist

This checklist closes the PRD-001 workstream 5 requirement for audit access, retention guidance, and tenant-isolation review before pilot launch.

## Audit Contract

Required tenancy lifecycle events:

- `tenant_created`
- `org_node_created`
- `org_node_updated`
- `org_node_moved`
- `org_node_deactivated`

Required audit record shape:

- `tenant_id`
- `actor_user_id`
- `action`
- `auditable_type`
- `auditable_id`
- `metadata`
- `created_at`

Metadata guidance:

- Include the fields needed to reconstruct the business change without joining across tenants.
- Prefer before/after values for updates and moves.
- Include structural context for org-node writes such as `parent_id`, `depth`, `node_type`, and import row keys when relevant.

## Retention Policy

- Phase 1 minimum retention commitment is 12 months.
- The admin audit review surface only reads rows inside the current 12-month compliance window.
- Any future pruning or export workflow must preserve at least 12 months of tenant audit history before deletion.

## Security Review Checklist

- Confirm tenant-owned routes use `auth`, `tenant`, and `admin` middleware where required.
- Confirm audit review queries filter by the current tenant context and do not expose cross-tenant rows.
- Confirm lifecycle events contain only the tenant-scoped identifiers and metadata required by analytics consumers.
- Confirm audit rows contain actor, action, auditable entity, and meaningful metadata for every supported tenancy admin workflow.
- Confirm audit indexes support tenant-scoped review over recent records.

## Pilot Sign-Off Notes

- Analytics consumers must subscribe to the lifecycle contract above before pilot launch.
- Security review should verify tenant isolation behavior using the tenancy feature tests and the audit review route.
