# PRD-003 Implementation Plan: Role-to-Skill Mapping UI

## Document Control
- Status: Draft for review
- Related PRD: `docs/product/prd/PRD-003-role-to-skill-mapping-ui.md`
- Pillar alignment: Skills Intelligence + Role-Based Pathways (Pillar 1)
- Last updated: 2026-04-05

## Purpose
Translate PRD-003 into a delivery plan that gets a usable role-to-skill mapping workflow live as quickly as possible while allowing multiple engineers or agents to work in parallel with minimal merge friction.

## Current State Summary
The repository has the platform foundations that PRD-003 can build on:
- Tenant-aware domain structure under `domains/*`.
- Tenant and organization hierarchy constraints in the `Tenancy` domain.
- Baseline admin surfaces and route-driven domain component test patterns.
- Existing Blade and Laravel domain registration patterns that can support a new admin workflow quickly.

The repository does not yet include the core PRD-003 building blocks:
- No `Role`, `Skill`, or role-to-skill mapping domain artifacts.
- No published-versus-draft mapping model.
- No mapping authoring UI, import/export flow, or completeness reporting.
- No downstream contracts yet for PRD-004/005/006 to consume published mappings.

## Planning Assumptions
- A new `Skills` domain is the fastest path because PRD-003, PRD-004, and PRD-005 all depend on shared role/skill concepts.
- Q2 editing access remains limited to tenant-level admins until PRD-002 delegated permissions are hardened.
- GA does not require a full job architecture product; one tenant-scoped mapping per role is sufficient for the first release.
- CSV import is required for the GA candidate, but it should not block the internal alpha slice.
- Downstream consumers only need a stable published mapping contract and version metadata in Q2, not a full event-driven pipeline on day one.

## Recommended Delivery Approach

### Option A: Build full role, skill, mapping, import, and manager visibility together before exposing UI
- Pros: fewer later contract changes.
- Cons: slower time to first pilot value and higher coordination overhead.

### Option B: Ship a narrow publishable mapping slice first, then layer import/completeness/reporting in parallel
- Pros: fastest path to visible value, easiest to validate with pilot admins, and best fit for concurrent execution.
- Cons: requires discipline to keep the first slice small and avoid overbuilding.

Decision: Option B.

## Outcomes This Plan Optimizes For
- An admin can create and publish a role mapping in-product without engineering help.
- Published mappings have stable version identifiers that PRD-004, PRD-005, and PRD-006 can consume.
- The implementation can be split into clear workstreams with low file overlap.
- The pilot team can seed 20-50 priority roles quickly without waiting on broader platform work.

## Proposed Domain Shape
- New domain: `domains/Skills`
- Primary concepts:
  - `Role`
  - `Skill`
  - `RoleSkillMapping`
  - `RoleSkillMappingVersion`
  - `MappedRoleSkill`
- Primary surfaces:
  - admin authoring UI for draft and publish flows
  - manager read-only mapping viewer
  - import/export endpoints
  - downstream published-mapping query contract

This keeps role and skill ownership in one bounded area and avoids spreading PRD-003 logic across `Tenancy`, `IdentityAccess`, and future pathway domains.

## Thin Vertical Slice
This is the fastest meaningful end-to-end release target:
1. Admin can open a role mapping workspace.
2. Admin can create a draft mapping for one role.
3. Admin can add skills, target proficiency, importance, and rationale.
4. System validates publish rules.
5. Admin can publish a versioned mapping.
6. Manager can view the published mapping read-only.

Everything else should be treated as parallel or follow-on work unless it blocks this slice directly.

## Workstreams

### 1. Skills Domain Foundation
Scope:
- Create the new `Skills` domain structure.
- Introduce tenant-scoped role, skill, draft mapping, published version, and mapped skill persistence.
- Define enums/data objects for importance weights and proficiency bands.
- Add core services for draft save, publish, published lookup, and completeness summary.

Key deliverables:
- `domains/Skills/app/...` service and model layer.
- `domains/Skills/database/migrations/...` for role, skill, mapping, version, and mapped-skill tables.
- Seed/factory support for roles and skills.
- Domain tests covering persistence and version semantics.

Acceptance criteria:
- Draft mappings can be created and updated safely per tenant.
- Publish creates an immutable version record.
- One role can have a current draft and a current published version without ambiguity.
- Published mappings can be queried by role and tenant through a stable service contract.

Dependencies:
- Tenancy constraints and current tenant context.

Owner candidates:
- Engineering (Skills/Platform)

### 2. Admin Authoring UI
Scope:
- Build the guided admin workspace described in PRD-003.
- Support role selection, mapped skill editing, validation feedback, draft save, compare-to-published, and publish action.
- Keep the first UI narrow and Blade-first for speed.

Key deliverables:
- Skills admin routes and controllers.
- Mapping workspace view with role search/selector.
- Add/remove skill interactions.
- Draft versus published comparison panel.
- Publish confirmation and validation states.

Acceptance criteria:
- Tenant admin can complete the thin vertical slice without raw database edits.
- Validation errors are understandable in-product.
- Publish action clearly shows impact and published state.

Dependencies:
- Workstream 1 service contracts.
- Minimum admin authorization gate.

Owner candidates:
- Engineering (Skills/UI)
- Product/Design

### 3. CSV Import, Export, and Setup Acceleration
Scope:
- Add CSV import dry-run and commit support for role-skill mappings.
- Add export for published mappings.
- Add starter template support to accelerate pilot setup.

Key deliverables:
- CSV contract and downloadable template.
- Dry-run validation service with row-level errors.
- Commit import flow that writes drafts or publishes according to policy.
- Published mapping export endpoint.

Acceptance criteria:
- Admin can validate import files before commit.
- Import rejects duplicate skills, missing proficiency, and invalid weights.
- Export returns published mappings in a predictable format that pilot teams can review offline.

Dependencies:
- Workstream 1 data model.
- Workstream 2 route and authorization patterns.

Owner candidates:
- Engineering (Skills/Import)
- Product for template design

### 4. Downstream Contracts and Refresh Hooks
Scope:
- Expose the published mapping contract needed by PRD-004, PRD-005, and PRD-006.
- Emit lifecycle events and add minimal refresh triggers on publish.
- Avoid building full downstream scoring in this PRD.

Key deliverables:
- Published mapping query service or endpoint.
- Mapping version reference contract for employee profile and recommendation consumers.
- Publish instrumentation and audit metadata.
- Lightweight refresh hook or queue event on publish.

Acceptance criteria:
- Downstream domains can request the current published mapping and version for a role.
- Republish changes are traceable and attributable.
- Publish events are available for analytics and future scoring refresh jobs.

Dependencies:
- Workstream 1 publish/version model.

Owner candidates:
- Engineering (Skills/Profiles/Assignments)
- Data

### 5. Manager Visibility and Completeness Reporting
Scope:
- Add read-only manager visibility for published mappings in scope.
- Add admin completeness indicators across roles for GA candidate readiness.

Key deliverables:
- Manager-facing published mapping view.
- Admin completeness dashboard or summary table.
- Ready/not-ready indicators for roles lacking publishable mappings.

Acceptance criteria:
- Managers can view published mappings for roles within approved scope.
- Admins can identify unmapped or incomplete priority roles quickly.
- Completeness logic matches publish rules and does not drift from backend validation.

Dependencies:
- Workstream 2 workspace patterns.
- Workstream 4 published contract.

Owner candidates:
- Engineering (Skills/UI)

### 6. Telemetry, Audit, and Route-Driven Test Hardening
Scope:
- Instrument lifecycle events from PRD-003.
- Add audited actions around publish and import.
- Add route-driven component tests across the main user workflows.

Key deliverables:
- Events:
  - `role_mapping_created`
  - `role_mapping_updated`
  - `role_mapping_published`
  - `role_mapping_imported`
- Audit metadata for editor, publish timestamp, and import actor.
- Feature tests for admin authoring, publish validation, import dry-run, import commit, and manager read-only access.

Acceptance criteria:
- Main role mapping workflows are covered through HTTP entrypoints.
- Publish and import actions produce the required audit and telemetry outputs.
- Test fixtures support tenant-safe coverage for multiple roles and skills.

Dependencies:
- Workstreams 1-5 depending on route availability.

Owner candidates:
- Engineering (Quality/Platform)
- Data

## Parallel Execution Plan

### Critical Path
The critical path should stay as short as possible:
1. Workstream 1 foundation
2. Thin parts of Workstream 2 authoring UI
3. Thin parts of Workstream 4 published contract
4. Thin parts of Workstream 6 route-driven tests

Once that path is complete, the product already has a usable internal alpha.

### Safe Parallel Split
Agent 1: Skills domain foundation
- Owns `domains/Skills/app`, `domains/Skills/database`, factories, and low-level tests.
- Must not edit UI views except for wiring required by new routes.

Agent 2: Admin authoring UI
- Owns `domains/Skills/resources/views`, controllers, and route registration for admin mapping screens.
- Consumes service contracts from Agent 1 without redesigning persistence.

Agent 3: Import/export acceleration
- Owns CSV services, requests, controller endpoints, and template/export behavior.
- Should depend on Agent 1 tables and Agent 2 auth patterns, but avoid editing core workspace views except import surfaces.

Agent 4: Downstream contracts, telemetry, and test hardening
- Owns publish hooks, event payloads, audit capture, manager read-only surface, and route-driven feature coverage.
- Should not change the core data model unless blocked by a missing publish contract.

### Merge Discipline
- Freeze the table and service contract shape early in Workstream 1.
- Keep all UI under `domains/Skills/resources/...` so UI ownership stays local.
- Prefer additive services over cross-agent edits to shared controllers.
- Use a single enum or data contract source for proficiency and importance to prevent drift across backend and UI.

## Proposed Sequence

### Phase 1: Internal Alpha Skeleton
- Workstream 1 foundation
- Thin slice of Workstream 2 workspace
- Thin slice of Workstream 4 published contract
- Thin slice of Workstream 6 route-driven tests

Exit criteria:
- Admin can create, edit, validate, and publish a role mapping for one role.
- Published mapping can be fetched by downstream consumers.
- Core publish workflow is test-covered.

### Phase 2: Pilot Beta Acceleration
- Remaining Workstream 2 polish
- Workstream 3 import/export
- Initial Workstream 5 manager read-only view
- Remaining telemetry and audit items from Workstream 6

Exit criteria:
- Pilot admins can seed mappings for priority roles quickly.
- Managers can view published mappings.
- Import and export are usable for setup and review.

### Phase 3: GA Candidate Hardening
- Remaining Workstream 5 completeness reporting
- Contract hardening for PRD-004 and PRD-005 consumers
- Validation, audit, and usability fixes from pilot feedback

Exit criteria:
- Priority role coverage can be monitored in-product.
- Published mappings are stable enough to support scoring and pathway suggestion rollout.
- The team can move into PRD-004 without reworking PRD-003 fundamentals.

## Milestone Proposal

### Sprint 1
- Stand up `Skills` domain and schema.
- Finalize proficiency and importance enums.
- Implement draft and publish service contracts.
- Ship the first authoring workspace shell.

### Sprint 2
- Complete edit and publish workflow.
- Add comparison against current published version.
- Expose published mapping contract for downstream consumers.
- Add core route-driven workflow tests.

### Sprint 3
- Implement CSV import dry-run, commit, and export.
- Add manager read-only visibility.
- Add lifecycle telemetry and audit coverage.

### Sprint 4
- Add completeness reporting and pilot setup refinements.
- Resolve feedback from internal alpha and design partner beta.
- Lock contracts for PRD-004 and PRD-005 handoff.

## Key Risks
- The team spends too long designing generic role and skill models.
  - Mitigation: constrain v1 to tenant-scoped roles, curated skill list, one mapping per role, and one current published version.
- Import complexity overtakes the authoring workflow.
  - Mitigation: keep CSV out of the critical path and ship it after manual publish works.
- Downstream teams begin PRD-004/005 against unstable mapping contracts.
  - Mitigation: define the published mapping read contract in Sprint 1 and treat it as the interface boundary.
- UI and persistence teams collide in the same files.
  - Mitigation: assign ownership by folder and keep controller logic thin over service contracts.
- Partial RBAC creates uncertainty around who may edit mappings.
  - Mitigation: hard-limit Q2 authoring to tenant admins and audit every publish/import action.

## Success Metrics
- Admin time to publish a prepared role mapping:
  - Baseline: 2-3 days via spreadsheet and review loops
  - Target: <= 30 minutes
  - Timeframe: first 30 days after pilot rollout
- Priority role mapping coverage:
  - Baseline: 0%
  - Target: >= 80%
  - Timeframe: by end of Q2
- Downstream contract readiness:
  - Baseline: 0 dependent workflows live
  - Target: PRD-004 and PRD-005 can consume published mappings without schema redesign
  - Timeframe: by GA candidate

## Immediate Next Decisions
- Confirm the Q2 GA proficiency model: 3 bands or 5 bands.
- Confirm whether pilot roles are created manually in-product or seeded from an initial curated library.
- Confirm whether manager read-only visibility should ship in beta or can land in the GA candidate if schedule compresses.
