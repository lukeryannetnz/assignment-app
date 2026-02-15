# PRD-002: RBAC Baseline for Enterprise Administration

## Document Control
- Status: Draft for review
- Pillar alignment: Enterprise Admin Platform + Integrations (Pillar 2)
- Roadmap phase alignment: Q1 baseline, Q2 admin console expansion
- Proposed owner candidates: Product (Enterprise Platform), Engineering (Identity/Access), Security, Design
- Last updated: 2026-02-15

## Problem Statement
Enterprise customers require predictable access control boundaries before broad rollout. Current access assumptions do not provide standardized role templates, scoped permissions by org level, or enterprise auditability expectations.

Without RBAC baseline, trust-by-design principle in `docs/product/PRODUCT_STRATEGY.md` is unmet and Q1/Q2 onboarding goals are at risk.

## Target Users and JTBD
- Enterprise Super Admin: Control tenant-wide governance and policy-critical actions.
- Org Admin (BU/Department): Manage users, assignments, and reporting within delegated scope.
- Manager: Assign and track pathways for direct/indirect teams.
- Learner: Access own profile, assignments, and progress.
- Internal Support (restricted): Troubleshoot tenant issues without overbroad data access.

JTBD:
- Assign least-privilege access by business role and org scope.
- Delegate operational admin tasks without compromising tenant governance.
- Audit who performed privileged actions and why.

## Goals and Non-Goals
### Goals
- Deliver baseline RBAC model with predefined enterprise roles and permission bundles.
- Enforce org-scoped access boundaries across admin APIs and UI surfaces.
- Provide auditable access-change logs and privileged action logs.
- Unblock Q2 admin console workflows (users, groups, assignment controls).

### Non-Goals
- Full ABAC/policy engine with custom condition expressions.
- Customer-defined custom roles in baseline release (defer to later phase).
- Just-in-time elevation workflows and approvals.

## Assumptions
- Tenancy and org hierarchy baseline (PRD-001) is available for scope anchoring.
- Most design partners can operate on standardized roles initially.
- Identity providers (SSO/SCIM) can map users into default roles with simple rules.

## Options and Tradeoffs
### Option A: Role-permission matrix only (no scope inheritance)
- Pros: simple implementation.
- Cons: does not support enterprise delegated administration well; manual overhead.

### Option B (Recommended): RBAC with org-scoped grants and inheritance
- Pros: aligns with enterprise structures; reduces admin burden; supports future delegated administration.
- Cons: more complex evaluation logic and testing.

Decision: Option B.

## Role Catalog (Baseline)
- `tenant_super_admin`: Full tenant governance and access administration.
- `org_admin`: Manage users/groups/assignments within assigned org subtree.
- `manager`: Manage assignments and progress visibility for managed teams.
- `learner`: Self-service learning experience only.
- `support_readonly` (internal, controlled): Read-only troubleshooting with tenant controls.

## Requirements
### Must
- Permission model primitives:
  - `role`
  - `permission`
  - `resource_type`
  - `scope` (tenant/org subtree/self)
- Authorization engine requirements:
  - Deny-by-default
  - Permission evaluation includes scope boundary
  - Explicit prevention of scope escalation by API/UI manipulation
- Assignment requirements:
  - User can hold multiple roles; effective permissions are union within permitted scopes
  - Role grants attach to tenant scope or specific org node scope
- Core permission bundles include:
  - User/group admin
  - Assignment create/update/cancel
  - Pathway visibility/reporting
  - Audit log read access (restricted to privileged roles)
- Audit requirements:
  - Log role grant/revoke events
  - Log privileged actions (assignment policy changes, bulk updates, org-level user operations)
  - Expose exportable audit records for enterprise admins
- API and UI enforcement:
  - Every admin endpoint requires RBAC check
  - UI must hide/disable unauthorized controls and handle stale permissions safely

### Should
- Pre-flight permission checker endpoint for admin console to reduce failed actions.
- Role assignment expiration date support for temporary delegated access.
- SCIM group-to-role mapping for top-priority identity providers.

### Could
- Custom role builder with guardrails.
- Break-glass emergency admin flow with explicit approvals.

## User Flows
1. Super Admin assigns `org_admin` role scoped to Department A.
2. Org Admin imports users and creates team assignments within Department A subtree.
3. Manager views team progress and creates assignments only for managed teams.
4. Audit reviewer exports last 30 days of privileged access changes.

## Success Metrics and Instrumentation
- Unauthorized access error rate (blocked actions due to authorization):
  - Baseline: N/A (new model)
  - Target: <= 3% of admin actions after 30-day stabilization (indicates role design clarity)
  - Timeframe: first month after beta rollout
- Delegated admin task completion without super admin intervention:
  - Baseline (assumption): <= 30% in manual early pilots
  - Target: >= 70% in design partner beta
  - Timeframe: Q2 midpoint
- Access-control incident count (P1/P2 security incidents tied to authorization defects):
  - Baseline: 0 target from launch
  - Target: 0
  - Timeframe: continuous, reviewed monthly

## Dependencies
- PRD-001 tenancy and hierarchy data model.
- Identity services (user lifecycle, SSO claims mapping, SCIM role mapping).
- Admin console v1 surfaces for user/group/assignment management.
- Security/compliance alignment on audit data retention and export controls.

## Risks and Mitigations
- Risk: role catalog too rigid for varied enterprise needs.
  - Mitigation: collect exception requests during beta; prioritize custom-role phase based on frequency.
- Risk: scope inheritance bugs expose cross-org data.
  - Mitigation: contract tests for inheritance boundaries; red-team style auth testing before GA.
- Risk: excessive authorization failures reduce admin trust.
  - Mitigation: permission pre-flight checks, clearer role setup UX, implementation playbooks.

## Rollout Plan
- Phase 1 (Q1 internal):
  - Implement authorization primitives and API middleware checks.
  - Validate baseline roles with internal staging tenant scenarios.
- Phase 2 (Q1 design partner beta):
  - Enable baseline roles for design partners with guided setup.
  - Monitor denied-action telemetry and audit log completeness.
- Phase 3 (Q2 MVP hardening):
  - Expand SCIM mapping support.
  - Ship admin console role-management UX improvements.

## Exit Criteria
- Baseline roles available and enforceable across admin APIs.
- Org-scoped role grants validated in integration and security testing.
- Audit logs complete for role changes and privileged actions.
- Design partners can onboard and operate delegated administration without engineering intervention for routine tasks.

## Open Questions
- Is `support_readonly` exposure acceptable for all enterprise tiers, or enterprise-only add-on?
- Which permission bundles should be contractually guaranteed in first enterprise SLA?
- What is the minimum acceptable role-propagation latency target for high-scale tenants?
