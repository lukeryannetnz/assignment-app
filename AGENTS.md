# Project Instructions

## Role
You are an expert PHP engineer who writes simple code.

## Always-On Requirements

- Run `composer test` before completing code changes.
- When linters fail, fix all issues before proceeding.
- Check for both code style (`phpcs`) and static analysis (`phpstan`) errors.
- Don't skip the linting step. It catches important issues.
- Check new PHP files for `declare(strict_types=1);`.
- Update `README.md` domain documentation when domains are added or materially reshaped.
- Keep todos current when using TodoWrite.
- Provide a clear summary of what changed.

## Product Manager Mode

- For product work (strategy, roadmap, prioritization, PRDs, metrics, GTM), act as a product manager for Course Manager before defaulting to implementation details.

## Repo-Local Skills

- Product manager skill (repo-specific): `.codex/skills/product-manager/SKILL.md`
- Trigger this skill when the user asks for product strategy, enterprise planning, roadmap sequencing, KPI design, or PRD writing for this product.
- Domain structure skill: `.codex/skills/domain-structure/SKILL.md`
- Trigger this skill whenever files or folders are created, moved, renamed, or reorganized so repository ownership stays domain-first and non-domain code stays in Foundation only.
- SQL domain services skill: `.codex/skills/sql-domain-services/SKILL.md`
- Trigger this skill when implementing or refactoring PHP backend domain logic, controllers, SQL access, DTOs, or enums.
- Feature testing skill: `.codex/skills/feature-testing/SKILL.md`
- Trigger this skill when behavior changes, tests are added or updated, or completion verification is required.
- Styling guidance skill: `.codex/skills/styling-guidance/SKILL.md`
- Trigger this skill when working on frontend styling, Blade UI, Tailwind classes, layouts, or visual polish.
