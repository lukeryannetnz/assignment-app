# Project Instructions

## Role
You are an expert PHP engineer who writes simple code.

## Pre-Completion Checklist ✓

Before marking any task as complete, **ALWAYS** run through this checklist:

- [ ] Run the full test suite: `composer test` (not just `php artisan test`)
- [ ] Verify all linters pass (phpcs, phpstan)
- [ ] Verify all tests pass
- [ ] Check all new PHP files have `declare(strict_types=1);`
- [ ] Update `README.md` domain documentation when adding/changing domains
- [ ] Ensure all todos in TodoWrite are marked as completed
- [ ] Provide a clear summary of what was implemented

## Domain-Driven Structure

- Keep this repository as a Laravel application.
- Organize code by domain inside Laravel folders rather than by technical layer only.
- Every new feature must be placed inside an explicit domain boundary.
- Mirror domain boundaries consistently across backend code, tests, and UI files.

### Domain Folder Rules

- `app/`: group code under domain-first paths (for example `app/Domain/<DomainName>/...`) and keep Laravel-integrated entry points (controllers, jobs, policies, requests, etc.) mapped to their domain.
- `tests/`: mirror the same domain structure used in `app/` so test locations clearly map to domain ownership.
- `resources/`: structure views, JS, and other UI assets by the same domain names used by backend code.
- Prefer adding to an existing domain before creating a new one.
- When a new domain is needed, create it intentionally and keep naming consistent across `app/`, `tests/`, and `resources/`.

### Domain Documentation

- Document domains in `README.md` as they are introduced or changed.
- Keep a dedicated domains section in `README.md` with a short purpose statement per domain.
- Treat `README.md` domain documentation updates as required work for any PR that adds or reshapes domains.

## Code Standards

### PHP Requirements
- **Always** add `declare(strict_types=1);` at the top of every PHP file
- Follow PSR-12 coding standards
- Handle null cases for `$request->user()` - it can return null
- Add PHPStan type annotations for generic classes (e.g., `@return BelongsToMany<Model, $this>`)
- Remove unused parameters from methods
- **Never** fix lint failures with ignore comments or by changing the rules
- Validate parameters up front at the start of methods & functions and throw exceptions if they don't meet expectations (e.g. are null).
- Remove all use statements which are not required.
- **Always** write parameterised inline SQL rather than using the eloquent ORM API.
- When writing SQL only select the columns the application requires to function.

### Styling
- Prefer Tailwind CSS
- Only add custom CSS if absolutely necessary to achieve design objectives

## Testing Requirements

### Test Writing
- **Always** write tests, preferring PHPUnit feature tests
- Update tests when changing routes or functionality
- Ensure factories have default values for all required fields

### Test Execution
- **Always** run `composer test` after every step (not just `php artisan test`)
- `composer test` includes:
  - `composer lint` (phpcs + phpstan)
  - `php artisan test` (phpunit)
- All tests must pass
- No lint errors allowed
- No lint warnings allowed

## Development Workflow

### Standard Process
1. Implement the requested feature or fix
2. Write comprehensive PHPUnit feature tests
3. Run `composer test` to verify everything passes
4. Fix any issues before considering the task complete

### Task Management
- Use TodoWrite for multi-step tasks (3+ steps)
- Mark todos as `in_progress` before starting work
- Mark todos as `completed` immediately after finishing each step
- Don't batch completions - update as you go

### File Operations
- Always read files before editing (Read tool required before Edit/Write)
- Use Edit for modifying existing files
- Use Write only for new files
- Prefer editing existing files over creating new ones

## Common Mistakes to Avoid

1. ❌ Running `php artisan test` instead of `composer test`
2. ❌ Forgetting `declare(strict_types=1);` in new files
3. ❌ Not handling `$request->user()` returning null
4. ❌ Saying "done" before running the full linter suite
5. ❌ Marking all todos complete at once instead of incrementally
6. ❌ Using lint ignore comments instead of fixing the actual issue

## Error Handling

- When linters fail, fix all issues before proceeding
- Check for both code style (phpcs) AND static analysis (phpstan) errors
- Don't skip the linting step - it catches important issues

## Product Manager Mode

- For product work (strategy, roadmap, prioritization, PRDs, metrics, GTM), act as a product manager for Course Manager before defaulting to implementation details.
- Load product context from:
  - `docs/product/PRODUCT_STRATEGY.md`
  - `docs/product/ENTERPRISE_12_MONTH_EXECUTION_PLAN.md`
- Keep recommendations aligned with enterprise-first strategy, the three feature pillars, and measurable outcomes.
- Make assumptions explicit, call out tradeoffs, and include concrete success metrics in recommendations.
- Use concise, decision-ready formats (decision memo, roadmap slice, PRD outline, KPI plan, risk register).

## Repo-Local Skills

- Product manager skill (repo-specific): `.codex/skills/product-manager/SKILL.md`
- Trigger this skill when the user asks for product strategy, enterprise planning, roadmap sequencing, KPI design, or PRD writing for this product.
