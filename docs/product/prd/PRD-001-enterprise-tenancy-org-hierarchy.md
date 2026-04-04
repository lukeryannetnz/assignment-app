# PRD-001: Enterprise Tenancy and Organization Hierarchy Foundation

## Document Control
- Status: Approved
- Pillar alignment: Enterprise Admin Platform + Integrations (Pillar 2)
- Roadmap phase alignment: Q1 (Months 1-3)
- Proposed owner candidates: Product (Enterprise Platform), Engineering (Platform), Security
- Last updated: 2026-04-04

## Problem Statement
Course Manager cannot onboard or govern enterprise customers at scale without a production-grade tenancy model and organization hierarchy. Current assumptions are single-organization oriented and do not provide isolation, delegated administration, or reliable team-level assignment boundaries.

Without this foundation, Q1 exit criteria in `docs/product/ENTERPRISE_12_MONTH_EXECUTION_PLAN.md` cannot be met:
- Multi-tenant account model finalized
- Core entities production-ready (org, user, role, skill, pathway, assignment)
- <14 day setup time for first enterprise tenant

## Target Users and JTBD
- L&D Admin: Set up enterprise account structure and onboard business units safely.
- HRBP / Department Admin: Manage users and team structure for their org scope.
- Platform/Support Admin (internal): Provision enterprise tenant with predictable setup workflow.

JTBD:
- Create a tenant and org tree that mirrors real business structure.
- Isolate enterprise data, configuration, and access boundaries.
- Support assignment and reporting scopes at company, department, and team levels.

## Goals and Non-Goals
### Goals
- Launch a tenant model with strict data isolation between enterprise customers.
- Launch an org hierarchy model (company -> business unit -> department -> team) with configurable depth limits.
- Provide admin workflows for creating and maintaining org nodes.
- Expose scoped APIs/events required by pathway assignment and analytics instrumentation.
- Achieve pilot-ready setup workflow under 14 days for design partners.

### Non-Goals
- Full delegated administration policy matrix (handled in RBAC baseline PRD and future iterations).
- Advanced external directory sync conflict resolution beyond MVP fallback rules.
- M&A-style cross-tenant data migration tooling.

## Assumptions
- At least 3 design partners are available in Q1 for validation.
- Initial enterprise target segment mostly uses <= 4 hierarchy levels.
- Existing assignment and reporting features can consume org node IDs with minimal schema refactor.
- SSO/SCIM integration work will proceed in parallel but is not blocked by hierarchy UI completion.

## Approved Scope Decisions
- Default hierarchy depth limit for phase 1 tenants is 4.
- Contractors/vendors are modeled as org node members within the same workforce model in phase 1.
- Separate workforce entity types for contractors/vendors are deferred to a future iteration if needed.
- Minimum audit retention commitment for phase 1 is 12 months.
- CSV bulk import remains a Must requirement for pilot onboarding and will be built in this PRD scope.

## Options and Tradeoffs
### Option A: Single-level org model (flat groups only)
- Pros: faster initial build, low complexity.
- Cons: weak fit for enterprise structures, poor future compatibility with delegated admin and policy scopes.

### Option B (Recommended): Multi-level org hierarchy with bounded depth
- Pros: supports enterprise governance, future-proofs assignment and analytics scopeing, aligns with roadmap.
- Cons: higher implementation complexity and migration planning effort.

Decision: Option B.

## Requirements
### Must
- Tenant entity with immutable tenant ID, lifecycle status, and plan tier metadata.
- Hard data isolation by tenant ID across users, org nodes, assignments, pathway mappings, and analytics events.
- Org hierarchy entity supporting:
  - Parent-child relationships
  - Node type enum (company/business_unit/department/team)
  - Active/inactive lifecycle state
- Org admin workflows:
  - Create, rename, move (with cycle prevention), deactivate/reactivate org nodes
  - Bulk import via CSV for pilot onboarding
- Validation rules:
  - Maximum hierarchy depth configurable by tenant setting
  - No orphan nodes
  - No cycles
- API support:
  - CRUD endpoints for tenant and org hierarchy administration
  - Query endpoints for assignment/reporting scope resolution
- Event instrumentation:
  - `tenant_created`
  - `org_node_created`
  - `org_node_updated`
  - `org_node_moved`
  - `org_node_deactivated`
- Auditability:
  - Who changed what org structure and when
  - Retain audit records per compliance defaults

### Should
- Suggested hierarchy templates during onboarding by company size/industry.
- Dry-run import validation prior to org bulk upload commit.
- Guardrails for high-impact moves (e.g., moving nodes with >N users).

### Could
- Versioned org snapshots for rollback in admin mistakes.
- Visual org chart explorer for non-admin stakeholders.

## UX and Workflow Notes
- Initial setup flow:
  1. Create tenant shell
  2. Configure root org node
  3. Import/create hierarchy nodes
  4. Assign initial admin users by scope
  5. Validate assignment/reporting preview
- Error handling:
  - Blocking errors for invalid hierarchy operations
  - Clear remediation guidance for CSV import failures

## Success Metrics and Instrumentation
- Time-to-first-value (tenant provisioning to first pathway assignment):
  - Baseline (assumption): 21 days for manual setup in early pilots
  - Target: <= 14 days by end of Q1
  - Timeframe: Q1 close
- Org onboarding completion (tenant created + hierarchy valid + first admin active):
  - Baseline: 0% (new capability)
  - Target: >= 80% of design partners complete in <= 5 business days
  - Timeframe: Q1 close
- Hierarchy integrity error rate (failed validation ops / total hierarchy write ops):
  - Baseline: N/A
  - Target: <= 2% after first month in pilot
  - Timeframe: first 30 days post-launch

## Dependencies
- Identity foundation (user lifecycle, auth context propagation).
- Assignment service updates to consume org scope resolution.
- Analytics pipeline ready to ingest and attribute new org/tenant events.
- Security review for tenant isolation and audit log retention policy.

## Risks and Mitigations
- Risk: hierarchy complexity increases onboarding friction.
  - Mitigation: bounded depth, templates, dry-run imports, onboarding playbook.
- Risk: isolation defects create cross-tenant data exposure.
  - Mitigation: mandatory tenant boundary tests, security sign-off gate before pilot.
- Risk: downstream services not ready for org-scoped queries.
  - Mitigation: contract-first API schema and integration test checkpoints by month 2.

## Rollout Plan
- Phase 1 (internal alpha, month 2):
  - Enable tenant/org for internal test accounts only.
  - Validate isolation, hierarchy CRUD, audit logs.
- Phase 2 (design partner beta, month 3):
  - Enable 3-5 partner sandboxes.
  - Run onboarding with implementation playbook.
- Phase 3 (Q2 hardening):
  - Expand to new enterprise pilots with template library and import UX improvements.

## Exit Criteria (Q1)
- Tenant and org hierarchy entities production-ready with test coverage.
- Pilot design partner sandboxes live on new tenancy model.
- Measured setup duration <= 14 days for first complete enterprise onboarding.

## Approval Notes
- Approved on 2026-04-04 with scope decisions locked.
- Delivery sequencing and implementation tracking are maintained in `docs/product/prd/PRD-001-implementation-plan.md`.
