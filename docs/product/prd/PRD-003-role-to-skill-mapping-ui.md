# PRD-003: Role-to-Skill Mapping UI

## Document Control
- Status: Approved
- Pillar alignment: Skills Intelligence + Role-Based Pathways (Pillar 1)
- Roadmap phase alignment: Q2 (Months 4-6) Feature Pillar 1 GA
- Proposed owner candidates: Product (Skills Intelligence), Design, Engineering (Skills/Pathways), Data
- Last updated: 2026-04-05

## Problem Statement
Course Manager has strategic intent around role-based learning, but the product does not yet give admins a usable way to define which skills matter for which roles. The current roadmap assumes role and skill entities will exist, but without a mapping workflow the team cannot operationalize skill-gap analysis, pathway recommendations, or manager-led assignment at enterprise scale.

If Q2 continues to focus primarily on unfinished platform foundations, design partners will see little visible learner value and pilot momentum will weaken. This PRD intentionally pivots toward the first customer-facing workflow that makes Pillar 1 tangible while relying on only the minimum viable foundation from PRD-001 and PRD-002.

## Target Users and JTBD
- L&D Admin: Define the expected skill profile for priority roles without needing engineering support.
- HRBP / Talent Partner: Align role expectations to workforce planning and reskilling initiatives.
- Manager: Understand what skills a role requires before assigning learning.

JTBD:
- Map priority roles to a clear set of required skills and proficiency expectations.
- Maintain role definitions as job families evolve.
- Use role-to-skill mappings as the source of truth for gap scoring and learning recommendations.

## Goals and Non-Goals
### Goals
- Deliver an admin UI to create, edit, review, and publish role-to-skill mappings.
- Support proficiency targets and priority weighting for mapped skills.
- Make published mappings consumable by employee skill profiles, pathway suggestions, and manager assignment workflows.
- Enable fast setup for the first 20-50 priority roles in pilot tenants.

### Non-Goals
- AI-generated skills ontology creation from scratch.
- Full workforce planning suite or job architecture management.
- Customer-defined custom scoring formulas in the GA release.

## Assumptions
- PRD-001 tenancy and org hierarchy provide tenant-safe scoping for role and user records.
- PRD-002 RBAC baseline is incomplete, so Q2 will ship with constrained admin permissions rather than full delegated policy flexibility.
- Pilot customers can start with curated role libraries and manual admin review before publishing mappings broadly.

## Approved Scope Decisions
- A role mapping can exist in draft or published state.
- Published mappings are versioned at the role level so downstream scoring remains stable until republished.
- Each mapped skill supports:
  - importance weight (`critical`, `core`, `supporting`)
  - target proficiency band
  - optional rationale note visible to admins and managers
- GA supports manual mapping creation and CSV import; external HRIS role sync stays out of scope.

## Options and Tradeoffs
### Option A: Spreadsheet-style bulk mapping only
- Pros: fastest to ship for power admins with large role catalogs.
- Cons: poor discoverability, weak validation UX, harder for managers and HR partners to trust.

### Option B (Recommended): Admin UI with guided editing plus CSV import
- Pros: supports pilot usability, better validation, clearer foundation for downstream workflows.
- Cons: more product and design effort than bulk upload alone.

Decision: Option B.

## Requirements
### Must
- Role mapping workspace with:
  - role selector/search
  - mapped skill list
  - add/remove skills flow
  - target proficiency selection
  - importance weighting
- State model:
  - draft mapping
  - published mapping
  - published timestamp and editor attribution
- Validation rules:
  - no duplicate skill per role
  - at least one `critical` or `core` skill before publish
  - required proficiency must be selected for all published skills
- Import/export:
  - CSV import for role-skill pairs with dry-run validation
  - CSV export of published mappings
- Visibility:
  - admins can review mapping completeness across roles
  - managers can view published mappings for roles in their scope
- Instrumentation:
  - `role_mapping_created`
  - `role_mapping_updated`
  - `role_mapping_published`
  - `role_mapping_imported`

### Should
- Suggested starter templates by common business function.
- Side-by-side comparison between current draft and published mapping.
- Mapping completeness indicator to show which roles are ready for downstream scoring.

### Could
- Role duplication/cloning to speed setup across adjacent job families.
- Inline recommendation prompts for potentially missing skills based on similar roles.

## UX and Workflow Notes
- Primary workflow:
  1. Admin selects a role.
  2. Admin adds required skills and sets target proficiency and importance.
  3. System validates completeness and highlights conflicts.
  4. Admin publishes the mapping.
  5. Downstream gap scoring refreshes for employees assigned to that role.
- UX should optimize for confidence, not raw speed. Admins need to understand what is published and what impact republishing will have.

## Success Metrics and Instrumentation
- Role mapping coverage:
  - Baseline: 0% of roles mapped in-product
  - Target: >= 80% of priority pilot roles mapped and published
  - Timeframe: by end of Q2
- Time to publish a new role mapping:
  - Baseline (assumption): 2-3 days via manual spreadsheets and review loops
  - Target: <= 30 minutes for a prepared admin
  - Timeframe: first 30 days after pilot rollout
- Downstream usability:
  - Baseline: no dependent workflows operational
  - Target: >= 70% of mapped roles are actively used in employee scoring or assignment workflows
  - Timeframe: by end of Q2

## Dependencies
- Skills taxonomy service and role entity model from Q1 design validation.
- PRD-001 tenant and org scoping.
- Minimum admin permission controls from PRD-002 sufficient to restrict editing to authorized admins.
- Event pipeline support for mapping lifecycle telemetry.

## Risks and Mitigations
- Risk: role owners disagree on required skills, delaying publish.
  - Mitigation: support draft state, rationale notes, and pilot setup workshops with recommended starter templates.
- Risk: incomplete RBAC allows wider edit access than ideal.
  - Mitigation: limit Q2 authoring access to tenant-level admins and audited actions until delegated permissions are hardened.
- Risk: poor role mapping quality weakens all downstream scoring and recommendations.
  - Mitigation: publish validation rules, template library, and pilot success review before broad rollout.

## Rollout Plan
- Phase 1 (Q2 month 4 internal alpha):
  - Admin-only role mapping editor for internal test tenants.
  - Validate publishing model and downstream refresh behavior.
- Phase 2 (Q2 month 5 design partner beta):
  - Enable guided setup for top pilot roles.
  - Measure time-to-publish and validation failure patterns.
- Phase 3 (Q2 month 6 GA candidate):
  - Ship CSV import, completeness reporting, and manager visibility.

## Exit Criteria
- Pilot admins can create and publish role-to-skill mappings without engineering intervention.
- Published mappings drive downstream employee scoring and pathway suggestion workflows.
- Priority pilot roles reach >= 80% published mapping coverage.

## Open Questions
- Should role libraries launch with one horizontal template set, or with 2-3 verticalized starter packs?
- What is the minimum proficiency model for GA: 3 bands or 5 bands?
- Do customers expect one global mapping per role title, or scoped variants by business unit?
