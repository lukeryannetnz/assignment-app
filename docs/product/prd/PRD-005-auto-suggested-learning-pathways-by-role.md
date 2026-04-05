# PRD-005: Auto-Suggested Learning Pathways by Role

## Document Control
- Status: Approved
- Pillar alignment: Skills Intelligence + Role-Based Pathways (Pillar 1)
- Roadmap phase alignment: Q2 (Months 4-6) Feature Pillar 1 GA
- Proposed owner candidates: Product (Pathways), Engineering (Recommendations/Assignments), Design, Content Operations
- Last updated: 2026-04-05

## Problem Statement
Even if role mappings and skill-gap scores exist, Course Manager still fails its core promise if admins and managers must manually determine what learning should be assigned next. The product needs a recommendation layer that turns role definitions and skill gaps into suggested pathways fast enough to support pilot deployment and manager adoption.

This feature is the clearest Q2 pivot from invisible foundation work to visible enterprise value: identify what a role needs, show what an employee is missing, and suggest a learning path that closes the gap.

## Target Users and JTBD
- L&D Admin: Configure and approve pathway suggestions for priority roles without curating every assignment from scratch.
- Manager: Quickly identify the most relevant pathway to assign for a team member based on role needs.
- Employee / Learner: Receive learning that feels relevant to current role expectations.

JTBD:
- Match roles and skill gaps to relevant pathways.
- Reduce manual assignment overhead for admins and managers.
- Improve activation and completion by making learning recommendations role-relevant.

## Goals and Non-Goals
### Goals
- Generate suggested learning pathways based on published role mappings and employee gap profiles.
- Present clear explanation for why a pathway is suggested.
- Allow admins/managers to review, accept, or ignore suggestions before assignment.
- Establish recommendation feedback signals for future tuning.

### Non-Goals
- Fully autonomous assignment with no human review in GA.
- Consumer-style recommendation personalization based on broad behavioral similarity.
- Dynamic content authoring or pathway generation from arbitrary course catalogs.

## Assumptions
- Pathway entities exist or can be modeled for Q2 use.
- Role mappings from PRD-003 and gap scoring from PRD-004 are available for at least a subset of pilot roles.
- Recommendation quality can rely on rule-based matching in GA rather than ML ranking.

## Approved Scope Decisions
- GA suggestions are recommendation candidates, not automatic enrollments.
- Suggestions are generated from explicit role-skill-pathway coverage rules and available evidence of gap severity.
- Every suggestion must show the top skills it addresses and the role context that triggered it.
- When no strong match exists, the system should say so rather than fabricate a weak recommendation.

## Options and Tradeoffs
### Option A: Admin-curated pathway library per role
- Pros: higher trust, easier to explain, low algorithm risk.
- Cons: high setup burden, weak scalability, slower to adapt to individual gaps.

### Option B (Recommended): Rules-based auto-suggestions with human approval
- Pros: reduces admin effort, aligns to Q2 activation goals, keeps enterprise control in the loop.
- Cons: requires stronger metadata quality across roles, skills, and pathways.

Decision: Option B.

## Requirements
### Must
- Recommendation engine inputs:
  - published role-to-skill mappings
  - employee gap profile
  - pathway metadata with covered skills and difficulty/level
- Suggestion outputs:
  - suggested pathway
  - addressed skills
  - recommendation rationale
  - target employee or role cohort
  - confidence/fit signal
- UI surfaces:
  - admin review queue for suggested pathways by role or employee
  - manager view for suggested next actions on team members
  - learner-facing explanation once assigned
- Controls:
  - accept and assign
  - dismiss
  - save for later review
- Instrumentation:
  - `pathway_suggestion_generated`
  - `pathway_suggestion_viewed`
  - `pathway_suggestion_accepted`
  - `pathway_suggestion_dismissed`

### Should
- Bulk approve suggestions for cohorts with the same role and similar gaps.
- Explain when a higher-priority gap is not yet covered by any available pathway.
- Ranking logic that prefers pathways covering critical skills first.

### Could
- Suggested pathway bundles for common progression sequences within a role.
- Recommendation tuning controls for admins by role family.

## UX and Workflow Notes
- Admin workflow:
  1. Admin selects a role or cohort.
  2. System surfaces recommended pathways tied to top skill gaps.
  3. Admin approves suggestions for assignment or saves them for review.
- Manager workflow:
  1. Manager sees suggested next pathways on employee progress pages.
  2. Manager assigns with minimal edits.
- Learner experience should emphasize relevance and explainability, not algorithm novelty.

## Success Metrics and Instrumentation
- Suggestion acceptance rate:
  - Baseline: 0% because no suggestion workflow exists
  - Target: >= 50% of surfaced suggestions are accepted by admins or managers
  - Timeframe: by end of Q2 pilot period
- Assignment activation:
  - Baseline: roadmap target exists but product workflow does not
  - Target: >= 50% assigned pathway activation rate within pilot accounts
  - Timeframe: by Q2 close
- Manual curation reduction:
  - Baseline (assumption): admins manually curate nearly all role-based assignments
  - Target: >= 40% reduction in manual assignment setup time for mapped roles
  - Timeframe: first 45 days after beta rollout

## Dependencies
- PRD-003 role-to-skill mapping UI and published mappings.
- PRD-004 employee profile and gap-scoring outputs.
- Pathway metadata model with skill coverage tags.
- Minimum permissioning from PRD-002 for admin and manager assignment actions.

## Risks and Mitigations
- Risk: low-quality pathway metadata produces weak recommendations.
  - Mitigation: require skill coverage tagging for GA-eligible pathways and expose coverage gaps in admin UI.
- Risk: suggestion volume overwhelms managers.
  - Mitigation: rank by criticality, limit surfaced suggestions, and support bulk review at role level.
- Risk: customers fear black-box recommendations.
  - Mitigation: keep model rules-based and show rationale on every suggestion.

## Rollout Plan
- Phase 1 (Q2 month 4 internal alpha):
  - Build role-skill-pathway matching logic with seeded pathways.
- Phase 2 (Q2 month 5 design partner beta):
  - Launch admin review queue and manager suggestion cards.
  - Measure acceptance and dismissal reasons.
- Phase 3 (Q2 month 6 GA candidate):
  - Add cohort bulk review and improve ranking for critical-skill coverage.

## Exit Criteria
- Suggested pathways are available for mapped pilot roles with visible rationale.
- Admins and managers can accept or dismiss suggestions without engineering support.
- Acceptance rate reaches >= 50% in pilot use, indicating baseline recommendation quality is useful.

## Open Questions
- What minimum metadata coverage is required before a pathway is eligible for recommendation?
- Should role-level curation override employee-level recommendations when both exist?
- How many suggestions per employee or role should be shown before choice overload harms conversion?
