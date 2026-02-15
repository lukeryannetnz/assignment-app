# Course Manager Enterprise 12-Month Execution Plan

## Planning Horizon
12 months from strategy approval date.

## Objective
Execute an enterprise-first roadmap across three strategic feature pillars:
1. Skills Intelligence + Role-Based Pathways
2. Enterprise Admin Platform + Integrations
3. Outcomes & ROI Analytics

## Delivery Phases

### Q1 (Months 1-3): Enterprise Foundation + Design Validation
Goal: establish enterprise readiness and de-risk core architecture.

Key outcomes:
- Multi-tenant account model finalized.
- Role/skill domain model approved.
- Pilot customer design partners recruited (3-5 organizations).

Major deliverables:
- Enterprise tenancy, org hierarchy, and RBAC baseline.
- SSO (SAML/OIDC) MVP and user provisioning framework.
- Skills taxonomy service (initial schema + admin CRUD).
- Pathway object model and assignment workflow specification.
- Analytics event tracking plan and instrumentation baseline.

Exit criteria:
- Design partner sandbox live.
- < 14 days setup time for first enterprise tenant.
- Core entities production-ready: org, user, role, skill, pathway, assignment.

### Q2 (Months 4-6): Feature Pillar 1 GA + Pillar 2 MVP
Goal: ship skill-gap mapping and pathway execution while enabling admin rollout.

Key outcomes:
- Role-based pathway assignment is usable at team scale.
- Admins can onboard and manage users with core controls.

Major deliverables:
- **Pillar 1 (GA)**:
  - Role-to-skill mapping UI.
  - Employee skill profile and gap scoring.
  - Auto-suggested learning pathways by role.
  - Manager assignment and progress tracking workflows.
- **Pillar 2 (MVP)**:
  - SCIM provisioning.
  - Group/team assignment policies.
  - Admin console v1 (users, groups, permissions, assignment controls).

Exit criteria:
- 70%+ of pilot learners have mapped role skills.
- 50%+ assigned pathway activation rate within pilot accounts.
- Admin onboarding completion without engineering intervention for pilot tenants.

### Q3 (Months 7-9): Integrations Scale + ROI Analytics Beta
Goal: reduce deployment friction and prove measurable impact.

Key outcomes:
- Integration coverage supports broader enterprise adoption.
- Executives can see learning-to-outcome relationships.

Major deliverables:
- **Pillar 2 (GA)**:
  - HRIS connector pack (starting with top 1-2 systems by target segment).
  - LMS/LXP import/export and assignment sync endpoints.
  - Audit logs, policy controls, and compliance-ready reporting basics.
- **Pillar 3 (Beta)**:
  - Executive dashboard v1: adoption, completion, skill progression.
  - Baseline-to-current skill lift reporting by team/role.
  - Renewal health indicators (engagement risk, assignment coverage, manager activity).

Exit criteria:
- Time-to-onboard reduced to <= 7 days for standard deployments.
- 3 design partners actively using ROI dashboard monthly.
- First two customer QBRs run with platform-generated ROI reporting.

### Q4 (Months 10-12): Pillar 3 GA + Enterprise Hardening
Goal: convert outcomes capability into renewal and expansion engine.

Key outcomes:
- ROI analytics supports renewal conversations.
- Platform reaches enterprise reliability/security expectations.

Major deliverables:
- **Pillar 3 (GA)**:
  - ROI dashboard v2 with business-outcome overlays (retention, internal mobility proxies where available).
  - Custom reporting and BI export/API.
  - Skills risk alerts (critical role gap thresholds, low adoption flags).
- Platform hardening:
  - Performance optimization for large orgs.
  - Security/compliance controls uplift (auditability, data retention controls).
  - Admin lifecycle workflows (bulk actions, delegated administration improvements).

Exit criteria:
- ROI dashboard used in >= 60% of active enterprise accounts.
- Net revenue retention trend improves quarter-over-quarter.
- Renewal playbook standardized with product-based impact artifacts.

## Cross-Functional Workstreams

### Product & Design
- Maintain design partner cadence (biweekly).
- Run monthly roadmap review with Sales/CS to align on enterprise blockers.
- Define metric instrumentation requirements before each release.

### Engineering & Platform
- Enforce release train with quarterly GA targets.
- Integration architecture with reusable connector framework.
- Observability and reliability SLOs for enterprise accounts.

### Data & Analytics
- Single source metric model for product + customer success.
- Dashboard governance to ensure consistency in executive reporting.
- Skill-gap model tuning based on usage and feedback.

### Customer Success & GTM
- 30/60/90-day implementation playbook.
- QBR templates using Pillar 3 outputs.
- Expansion triggers based on team adoption and skills coverage.

## Dependency Map
- Pillar 1 depends on role/skill domain model and event tracking.
- Pillar 2 depends on tenant/RBAC architecture and identity foundation.
- Pillar 3 depends on complete event instrumentation and stable data pipeline.
- GTM scale depends on reducing onboarding friction via integrations.

## Milestone Calendar
- Month 1: architecture sign-off, design partner recruitment complete.
- Month 2: identity and tenant MVP in test.
- Month 3: pilot sandbox launch.
- Month 4: role/skill mapping and assignment workflows live in pilot.
- Month 5: manager workflows + SCIM MVP.
- Month 6: Pillar 1 GA for pilot accounts.
- Month 7: HRIS connector alpha.
- Month 8: integration GA candidate + ROI dashboard beta.
- Month 9: first QBRs with product ROI reporting.
- Month 10: ROI v2 and BI export alpha.
- Month 11: compliance and hardening completion.
- Month 12: Pillar 3 GA and expansion playbook rollout.

## KPI Targets By End Of Month 12
- Time-to-first-value: <= 7 days for standard enterprise onboarding.
- Assignment completion rate: >= 65% in active enterprise customers.
- Role skill coverage: >= 75% of active learners mapped.
- Manager monthly active rate: >= 40% in deployed teams.
- Net revenue retention: positive quarter-over-quarter trajectory.

## Risks, Assumptions, Mitigations
- Assumption: access to at least 3 design partners.
  - Mitigation: secure customer advisory group in Q1.
- Risk: integration backlog slows enterprise onboarding.
  - Mitigation: connector prioritization by target ICP + reusable integration framework.
- Risk: weak data quality undermines ROI trust.
  - Mitigation: mandatory event QA gates before feature GA.
- Risk: scope expansion across too many enterprise asks.
  - Mitigation: strict roadmap guardrails tied to three pillars only.

## Governance Cadence
- Weekly: squad delivery sync + risk review.
- Monthly: KPI review and roadmap reprioritization.
- Quarterly: release retrospective and next-quarter commitment lock.

