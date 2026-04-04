# PRD-001 Implementation Plan: Enterprise Tenancy and Organization Hierarchy

## Document Control
- Status: Draft for review
- Related PRD: `docs/product/prd/PRD-001-enterprise-tenancy-org-hierarchy.md`
- Pillar alignment: Enterprise Admin Platform + Integrations (Pillar 2)
- Last updated: 2026-04-04

## Purpose
Translate PRD-001 into a staged execution plan that is realistic against the current codebase and aligned to the enterprise-first roadmap.

## Current State Summary
The repository already includes the core tenancy foundation:
- Tenant-owned schema constraints across users, courses, sections, curriculum items, quiz questions, enrollment pivot, and org nodes.
- Request-scoped tenant context enforcement and tenant-aware application routes.
- Tenant metadata API and core org hierarchy CRUD operations.
- Hierarchy validation for bounded depth, cycle prevention, and orphan-active safeguards.
- Baseline audit logging for tenant and org node changes.
- Feature coverage for tenant isolation across major current flows.

The repository does not yet include the full PRD-001 scope:
- Internal platform admin tenant provisioning workflow.
- Org hierarchy CSV bulk import and dry-run validation.
- Tenancy admin UI under the `Tenancy` domain.
- Assignment/reporting scope-resolution APIs.
- Analytics-compatible tenant and org lifecycle instrumentation contracts.
- Pilot onboarding playbook support and compliance hardening.

## Planning Assumptions
- Default hierarchy depth limit remains 4 for phase 1 tenants.
- CSV bulk import stays in PRD-001 scope as a Must requirement.
- Contractors/vendors remain part of the same workforce model in phase 1.
- RBAC baseline work can proceed in parallel, but PRD-001 must provide stable org-scope anchors before RBAC can be completed cleanly.
- SSO/SCIM work may proceed in parallel, but pilot onboarding still needs a viable manual bootstrap flow without SCIM.

## Recommended Delivery Approach

### Option A: Finish backend completeness first, defer UI until later
- Pros: fastest path to technical completion of APIs and data contracts.
- Cons: weak fit for the product strategy principle of admin-first usability; onboarding will still require engineering support.

### Option B: Build pilot onboarding slice end-to-end first
- Pros: best fit for the PRD goal of <14 day setup and the roadmap requirement that pilot sandboxes go live with predictable onboarding.
- Cons: requires parallel work across backend, UI, and operational workflow earlier.

Decision: Option B.

## Outcomes This Plan Optimizes For
- Pilot tenant can be provisioned without direct database intervention.
- Org hierarchy can be created safely through UI or validated CSV import.
- Downstream assignment and reporting services can resolve org scopes through stable tenancy contracts.
- Security and audit expectations are strong enough for design partner pilots.

## Workstreams

### 1. Platform Foundation Completion
Scope:
- Add explicit internal tenant provisioning flow for platform/support admins.
- Create tenant shell, root org node bootstrap, and initial tenant audit baseline.
- Add explicit `tenant_created` event and audit semantics.

Key deliverables:
- Platform admin create-tenant endpoint and service.
- Root org node bootstrap behavior.
- Tenant bootstrap tests.

Acceptance criteria:
- A platform admin can provision a tenant shell without self-registration.
- Tenant creation records audit metadata and emits the required lifecycle event.
- Newly created tenants have valid default configuration, including depth limit 4.

Dependencies:
- Identity foundation for internal admin authentication.
- Final owner for platform admin workflow.

Owner candidates:
- Engineering (Platform)
- Security for event/audit review

### 2. Org Hierarchy Management Completion
Scope:
- Extend current hierarchy management with import-safe operational controls.
- Preserve current create/rename/move/deactivate/reactivate logic while adding pilot onboarding tooling.

Key deliverables:
- CSV import parser and validation service.
- Dry-run import endpoint with row-level error reporting.
- Commit import endpoint with transactional write behavior.
- Guardrails for high-impact moves based on subtree size thresholds.

Acceptance criteria:
- Admin can upload CSV and receive blocking validation feedback before commit.
- Import prevents cycles, depth violations, orphan creation, and malformed node types.
- Commit imports are transactional per upload and create audit entries for imported nodes.
- Large subtree moves trigger explicit confirmation or policy checks.

Dependencies:
- Final CSV contract and onboarding template examples.
- Tenancy admin UI or API client surface for operators.

Owner candidates:
- Engineering (Platform)
- Product for import UX and error language

### 3. Tenancy Admin UI
Scope:
- Introduce `resources/domains/tenancy/...` and the corresponding domain UI for tenant setup and hierarchy management.
- Support the PRD onboarding flow from tenant shell to hierarchy validation.

Key deliverables:
- Tenant settings page.
- Org hierarchy list/tree management interface.
- CSV import review surface with dry-run results.
- Empty states, validation messages, and remediation guidance.

Acceptance criteria:
- Internal admins can complete the setup flow in-product.
- Validation failures are understandable without reading logs.
- The UI supports create, rename, move, deactivate, reactivate, import dry-run, and import commit.

Dependencies:
- Platform provisioning endpoints.
- Organization import APIs.
- Existing domain view registration patterns.

Owner candidates:
- Engineering (Platform/UI)
- Product/Design

### 4. Assignment and Reporting Scope Contracts
Scope:
- Add tenancy APIs that allow other domains to resolve subtree membership and org scope boundaries.
- Keep contracts explicit and testable, matching the ADR direction.

Key deliverables:
- Scope-resolution endpoints for company, department, and team subtree queries.
- Service methods for ancestor/descendant resolution.
- Contract tests for downstream consumers.

Acceptance criteria:
- Assignment and reporting services can request valid org scopes through stable APIs.
- Scope queries are tenant-safe and reject cross-tenant references.
- APIs are documented well enough to support PRD-002 RBAC and future assignment features.

Dependencies:
- Stable org hierarchy IDs and lifecycle rules.
- Alignment with assignment and analytics consumers.

Owner candidates:
- Engineering (Platform)
- Engineering owning assignment/reporting consumers

### 5. Analytics, Audit, and Compliance Hardening
Scope:
- Ensure lifecycle events and audit data meet pilot compliance and measurement needs.
- Close the gap between audit storage and usable compliance behavior.

Key deliverables:
- Event contract for `tenant_created`, `org_node_created`, `org_node_updated`, `org_node_moved`, and `org_node_deactivated`.
- Audit metadata schema guidance.
- Retention policy implementation or documented enforcement approach for 12-month minimum retention.
- Security review checklist for tenant isolation and audit access.

Acceptance criteria:
- Required lifecycle events are emitted consistently and test-covered.
- Audit records capture actor, action, entity, and relevant metadata.
- Retention policy and access controls are documented and reviewed before pilot launch.

Dependencies:
- Analytics pipeline readiness.
- Security review bandwidth.

Owner candidates:
- Engineering (Platform)
- Data/Analytics
- Security

### 6. Pilot Readiness and Operationalization
Scope:
- Turn the feature set into a repeatable design-partner onboarding motion.

Key deliverables:
- Pilot onboarding checklist.
- Seed/template examples for common org structures.
- KPI instrumentation for onboarding duration and hierarchy integrity error rate.
- Go/no-go checklist for internal alpha and design-partner beta.

Acceptance criteria:
- Internal team can provision and validate a pilot sandbox using the documented workflow.
- Setup duration can be measured from tenant creation to first valid assignment-ready hierarchy.
- Product, engineering, and customer-facing teams share the same onboarding playbook.

Dependencies:
- Functional completion of workstreams 1-5.
- At least one dry-run design-partner scenario.

Owner candidates:
- Product
- Customer Success
- Engineering

## Proposed Sequence

### Phase 1: Complete backend contracts
- Workstream 1
- Workstream 4
- Event/audit contract subset from Workstream 5

Exit criteria:
- Platform admin can provision tenant shell and root org node.
- Scope-resolution APIs exist and are test-covered.
- Required lifecycle events are defined and emitted.

### Phase 2: Complete operator onboarding workflows
- Workstream 2
- Workstream 3

Exit criteria:
- Admin can manage hierarchy through UI.
- CSV import dry-run and commit are usable for pilot onboarding.
- Validation errors are understandable and actionable.

### Phase 3: Pilot hardening
- Remaining Workstream 5 items
- Workstream 6

Exit criteria:
- Security review is complete.
- Audit retention approach is accepted.
- Internal alpha onboarding succeeds without engineering database intervention.

## Milestone Proposal

### Sprint 1
- Implement platform tenant provisioning flow.
- Add explicit `tenant_created` instrumentation.
- Define scope-resolution API contract.

### Sprint 2
- Implement scope-resolution services and endpoints.
- Add contract tests for assignment/reporting consumers.
- Begin tenancy admin UI skeleton.

### Sprint 3
- Implement CSV import parser, dry-run validation, and commit flow.
- Add import audit coverage and move guardrails.
- Complete tenancy admin UI for hierarchy operations.

### Sprint 4
- Complete analytics/audit hardening.
- Run internal alpha onboarding using the documented setup flow.
- Finalize pilot onboarding checklist and beta readiness sign-off.

## Key Risks
- CSV import complexity expands beyond pilot needs.
  - Mitigation: constrain the phase 1 CSV schema to required fields only and defer advanced reconciliation.
- UI work slips and leaves onboarding API-only.
  - Mitigation: ship a narrow internal admin UI focused on setup rather than a broad admin console.
- Scope-resolution contracts drift from assignment/reporting needs.
  - Mitigation: review API contracts with owners of PRD-002 and pathway/reporting work before implementation.
- Event instrumentation remains underspecified and blocks analytics trust.
  - Mitigation: define event payloads before coding and add QA gates for event presence.

## Success Metrics
- Tenant provisioning to assignment-ready hierarchy:
  - Baseline: 21 days assumption from PRD-001
  - Target: <= 14 days by pilot readiness
  - Timeframe: Q1 close
- Org onboarding completion:
  - Baseline: 0%
  - Target: >= 80% of design partners complete setup in <= 5 business days
  - Timeframe: Q1 close
- Hierarchy integrity error rate:
  - Baseline: N/A
  - Target: <= 2% of hierarchy write operations after first month in pilot
  - Timeframe: first 30 days post-launch

## Immediate Next Decisions
- Confirm whether the implementation plan should remain as a standalone execution artifact or be copied into a broader roadmap/backlog document.
- Confirm the owner for the internal platform admin provisioning workflow.
- Define the phase 1 CSV schema and sample import template before engineering starts the import service.
