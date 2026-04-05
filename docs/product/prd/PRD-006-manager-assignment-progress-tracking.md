# PRD-006: Manager Assignment and Progress Tracking Workflows

## Document Control
- Status: Approved
- Pillar alignment: Skills Intelligence + Role-Based Pathways (Pillar 1)
- Roadmap phase alignment: Q2 (Months 4-6) Feature Pillar 1 GA
- Proposed owner candidates: Product (Manager Experience), Engineering (Assignments/Reporting), Design, Customer Success
- Last updated: 2026-04-05

## Problem Statement
Course Manager's strategy depends on managers actively driving team learning, but the current product experience is admin-heavy and does not provide a practical manager workflow for assigning learning, monitoring progress, and following up on skill gaps. Without a manager operating surface, pathway recommendations and skill intelligence will not convert into behavior change or measurable outcomes.

After extended foundation work, this is the Q2 feature most likely to re-energize the team and customers because it puts a real day-to-day user in the loop and creates visible accountability for learning progress.

## Target Users and JTBD
- People Manager: Assign relevant learning to direct reports and monitor completion without needing full admin tooling.
- L&D Admin: Enable managers to take action while keeping governance and visibility controls intact.
- Employee / Learner: Receive role-relevant assignments with clear expectations and progress visibility.

JTBD:
- Assign role-aligned pathways to individuals or teams quickly.
- Track progress and identify stalled learners early.
- Follow up on critical skill gaps without relying on manual spreadsheets or admin requests.

## Goals and Non-Goals
### Goals
- Provide a manager workflow to assign pathways to direct reports or managed teams.
- Deliver a manager progress dashboard with completion, in-progress, overdue, and not-started states.
- Enable lightweight intervention actions such as nudges, reassignment, or due-date changes within policy.
- Support enterprise governance through scoped visibility and audited manager actions.

### Non-Goals
- Full performance management suite or 1:1 coaching workspace.
- Open-ended manager authoring of custom pathways in GA.
- Cross-org visibility beyond approved management scope.

## Assumptions
- Minimum manager role permissions from PRD-002 allow assignment and progress visibility for managed teams.
- Role mappings, skill profiles, and suggested pathways from PRD-003 through PRD-005 exist for at least pilot cohorts.
- Organization hierarchy from PRD-001 can resolve manager-to-team visibility boundaries.

## Approved Scope Decisions
- GA manager scope is limited to direct reports and explicit managed teams; skip matrix-management edge cases for now.
- Managers can assign from approved pathways or accepted suggestions, not from unrestricted catalog search.
- Progress tracking focuses on pathway status, key milestones, and top role-gap context rather than detailed course analytics.
- Nudges and reminders are in scope; escalation chains and approvals are deferred.

## Options and Tradeoffs
### Option A: Admin-only assignment with manager read-only dashboards
- Pros: lower permission and compliance complexity.
- Cons: weak manager engagement, slower intervention loop, misaligned with strategy principle of manager-in-the-loop.

### Option B (Recommended): Scoped manager assignment plus progress tracking
- Pros: creates the operating workflow needed for activation and completion; aligns directly to strategy.
- Cons: depends on enough RBAC and hierarchy integrity to enforce boundaries correctly.

Decision: Option B.

## Requirements
### Must
- Manager assignment capabilities:
  - assign approved pathways to individual direct reports
  - assign approved pathways to managed teams/cohorts
  - set due date and optional assignment note
- Progress dashboard:
  - team-level summary counts by status
  - employee-level progress view
  - overdue and stalled filters
  - top outstanding role-gap context
- Intervention actions:
  - send reminder/nudge
  - adjust due date
  - cancel assignment
- Governance:
  - enforce manager scope via org hierarchy and role permissions
  - audit manager assignment and intervention actions
- Instrumentation:
  - `manager_assignment_created`
  - `manager_progress_dashboard_viewed`
  - `manager_assignment_nudged`
  - `manager_assignment_due_date_changed`

### Should
- Team-level weekly digest for managers with at-risk assignments.
- Filters for role, team, assignment status, and critical skill gap.
- Simple notes/history log for assignment follow-up context.

### Could
- Manager coaching checklist tied to assignments.
- Slack or email reminder integration for nudges.

## UX and Workflow Notes
- Core workflow:
  1. Manager opens team dashboard.
  2. Manager sees employees with top role gaps and suggested pathways.
  3. Manager assigns approved learning with due date.
  4. Manager reviews progress weekly and nudges stalled learners.
- This experience should feel lightweight and operational. Managers are not administrators and should not see platform complexity.

## Success Metrics and Instrumentation
- Manager monthly active rate:
  - Baseline: effectively 0 for scoped assignment workflows
  - Target: >= 40% in deployed pilot teams
  - Timeframe: by end of Q2
- Pathway progress visibility:
  - Baseline: managers lack a dedicated workflow
  - Target: >= 80% of manager-created assignments are visible in dashboard status tracking within 5 minutes
  - Timeframe: first month after rollout
- Intervention effectiveness:
  - Baseline: no structured intervention workflow exists
  - Target: >= 20% higher completion rate for nudged overdue assignments versus unnudged overdue assignments
  - Timeframe: measure by end of Q2 where sample size is sufficient

## Dependencies
- PRD-001 tenant/org hierarchy for manager scope enforcement.
- PRD-002 baseline manager permissions and audit logs.
- PRD-005 suggested pathway workflow or approved pathway library.
- Assignment status and learning event instrumentation.

## Risks and Mitigations
- Risk: incomplete RBAC exposes assignments outside manager scope.
  - Mitigation: hard-limit Q2 to direct reports and explicit managed teams, with audited actions and test coverage around scope boundaries.
- Risk: managers ignore the workflow because it feels like extra admin work.
  - Mitigation: center the dashboard on exceptions, suggested next actions, and minimal-click assignment.
- Risk: too many reminder actions create notification fatigue.
  - Mitigation: add sensible limits, weekly digest preference, and clear overdue thresholds.

## Rollout Plan
- Phase 1 (Q2 month 4 internal alpha):
  - Validate manager scope resolution, assignment action model, and dashboard skeleton.
- Phase 2 (Q2 month 5 design partner beta):
  - Enable manager assignment for selected pilot teams.
  - Measure assignment creation, reminder usage, and dashboard return visits.
- Phase 3 (Q2 month 6 GA candidate):
  - Expand to managed team cohorts, overdue filters, and lightweight follow-up notes.

## Exit Criteria
- Managers in pilot tenants can assign approved pathways to scoped employees without admin intervention.
- Managers can monitor progress and take at least one intervention action from the dashboard.
- Manager monthly active rate reaches >= 40% in deployed pilot teams.

## Open Questions
- What is the minimum manager hierarchy model needed to support real enterprises without reopening broader RBAC scope?
- Should manager reminders default to in-app only, or include email in GA?
- How much assignment editing power is acceptable before admins want approval controls?
