# PRD-004: Employee Skill Profile and Gap Scoring

## Document Control
- Status: Approved
- Pillar alignment: Skills Intelligence + Role-Based Pathways (Pillar 1)
- Roadmap phase alignment: Q2 (Months 4-6) Feature Pillar 1 GA
- Proposed owner candidates: Product (Skills Intelligence), Engineering (Skills/Profiles), Data Science/Analytics, Design
- Last updated: 2026-04-05

## Problem Statement
Employees and managers currently lack a single, role-aware view of existing skills versus required skills. Without a credible employee skill profile and gap score, Course Manager cannot deliver on its strategy to identify workforce gaps, prioritize targeted learning, or prove improvement over time.

The organization has spent Q1 on multi-tenant and access foundations. To restore momentum and demonstrate user value in Q2, the product needs a visible skill intelligence experience that turns role mappings into actionable insight for learners, managers, and admins.

## Target Users and JTBD
- Employee / Learner: Understand how well current skills match role expectations and what to improve next.
- Manager: See where each team member is above, at, or below target for role-critical skills.
- L&D Admin: Identify where gaps cluster by role or team to prioritize interventions.

JTBD:
- Present a trusted skill profile for each employee.
- Translate raw skill evidence into a simple role-gap score.
- Use the score to trigger learning assignments and measure progress over time.

## Goals and Non-Goals
### Goals
- Launch an employee skill profile page with current role, mapped skills, current proficiency evidence, and gap indicators.
- Introduce a transparent baseline gap-scoring model tied to published role mappings.
- Allow managers and admins to review gap summaries for their scoped employees.
- Establish a scoring foundation that can improve later without changing the core user workflow.

### Non-Goals
- High-stakes talent decisions such as promotion, compensation, or performance review automation.
- AI-inferred proficiency from unverified external signals in GA.
- Advanced benchmarking against peers or external labor market datasets.

## Assumptions
- Published role-to-skill mappings from PRD-003 are available for at least pilot priority roles.
- Skill evidence in Q2 will come from a limited set of internal signals such as course/pathway completion, assessments, admin-entered validations, or profile attestations.
- PRD-002 RBAC remains partial, so visibility will be scoped to self, manager, and authorized admins only.

## Approved Scope Decisions
- Gap scoring for GA will be rules-based, explainable, and intentionally conservative.
- The primary output is a role-fit score plus per-skill gap status, not a predictive employability score.
- Missing data should appear as `insufficient evidence`, not as automatic failure.
- Managers can view scores for employees in managed scope but cannot edit evidence directly unless granted admin capabilities.

## Options and Tradeoffs
### Option A: Single aggregate score only
- Pros: easy to understand and market.
- Cons: low trust because users cannot see what drives the score.

### Option B (Recommended): Aggregate score plus skill-level explanation
- Pros: more transparent, more actionable for managers and learners, better fit for enterprise trust requirements.
- Cons: higher design and data-model complexity.

Decision: Option B.

## Requirements
### Must
- Employee skill profile includes:
  - current role
  - published role mapping version in use
  - skill list with target proficiency, current evidence, and gap status
  - overall role-gap score or fit band
- Gap model requirements:
  - use mapped skill importance weighting
  - distinguish `meets target`, `below target`, and `insufficient evidence`
  - calculate aggregate score only from available evidence with visible confidence rules
- Visibility and access:
  - employee can view own profile
  - manager can view direct/authorized team members
  - admin can view scoped workforce summaries
- Event instrumentation:
  - `employee_skill_profile_viewed`
  - `skill_gap_score_calculated`
  - `skill_evidence_updated`
- Auditability:
  - store score calculation timestamp and source mapping version
  - log manual evidence edits and overrides

### Should
- Time-series history showing whether gap score improved over prior periods.
- Team summary view for managers with sortable high-gap employees.
- Confidence indicator when evidence coverage is low.

### Could
- Employee self-reflection flow for adding provisional skills awaiting validation.
- Skill endorsements or manager confirmations as lightweight evidence inputs.

## UX and Workflow Notes
- Employee workflow:
  1. Learner opens skill profile.
  2. Learner sees current role-fit status and top gaps.
  3. Learner follows recommended pathway or assignment to close gaps.
- Manager workflow:
  1. Manager opens team view.
  2. Manager identifies employees with critical-role gaps or low evidence coverage.
  3. Manager assigns pathways or follows up with coaching.
- The scoring explanation must be clear enough that enterprise buyers can defend its use internally.

## Success Metrics and Instrumentation
- Skill profile adoption:
  - Baseline: 0 employees with profile visibility
  - Target: >= 60% of pilot learners view their skill profile at least once in the first 30 days
  - Timeframe: first month after rollout
- Role-gap coverage:
  - Baseline: 0% of pilot learners scored
  - Target: >= 70% of pilot learners in mapped roles have a visible score or evidence status
  - Timeframe: by Q2 close
- Manager actionability:
  - Baseline: no manager workflow exists
  - Target: >= 40% of active pilot managers view team gap summaries monthly
  - Timeframe: by Q2 close

## Dependencies
- PRD-003 published role-to-skill mappings.
- Skills evidence model and event capture from learning activity.
- PRD-001 tenant and org scoping.
- PRD-002 minimum access controls for self/manager/admin visibility.

## Risks and Mitigations
- Risk: users do not trust scores if the logic feels opaque.
  - Mitigation: use explainable scoring, visible evidence sources, and conservative handling of missing data.
- Risk: evidence coverage is too sparse for meaningful scores in early pilots.
  - Mitigation: surface `insufficient evidence`, prioritize required assessments for critical skills, and track confidence separately.
- Risk: score misuse for HR decisions creates customer concern.
  - Mitigation: position the score as learning guidance, add product copy on intended use, and defer high-stakes workflows.

## Rollout Plan
- Phase 1 (Q2 month 4 internal alpha):
  - Stand up profile page and baseline scoring logic with seeded data.
- Phase 2 (Q2 month 5 design partner beta):
  - Enable learner and manager views for mapped roles.
  - Validate score clarity and evidence trust with pilot users.
- Phase 3 (Q2 month 6 GA candidate):
  - Add team summaries, score history basics, and audit logging for manual adjustments.

## Exit Criteria
- Pilot learners in mapped roles can see a role-aware skill profile and gap score.
- Managers can view gap summaries for their teams and use them to assign learning.
- At least 70% of pilot learners in mapped roles have a visible profile with actionable gap status.

## Open Questions
- Which evidence sources are strong enough for GA scoring versus display-only context?
- Should the aggregate output be a 0-100 score, fit band, or both?
- What level of explanation is required for customer admins to trust and adopt the model?
