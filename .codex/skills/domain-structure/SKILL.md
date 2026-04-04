---
name: domain-structure
description: Use when creating, moving, renaming, or reorganizing files or folders in this repository so business-owned code stays inside explicit domains and framework composition stays in Foundation.
---

# Domain Structure Skill

Use this skill whenever files or folders are created, moved, renamed, or reorganized.

## Canonical Decision

Read `docs/adr/ADR-003-root-domain-structure.md` first. Use `docs/architecture/domain-structure.md` as the quick pointer.

## Apply These Rules

- Keep the repository Laravel-based, but place business-owned artifacts under `domains/<Domain>/...`.
- Use `domains/<Domain>/app`, `tests`, `resources`, and `database` as the default locations for domain code.
- Treat `domains/Foundation` as framework composition only. Do not move business logic there.
- Keep root Laravel entrypoints thin and compositional. `routes/web.php` should delegate to domain route files.
- Prefer extending an existing domain before creating a new one.
- Do not introduce catch-all business domains such as `Shared` or `Common`.
- If code does not belong to framework composition, it belongs in an explicit business domain.
- Keep naming domain-qualified:
  - PHP namespaces mirror the filesystem
  - views use domain namespaces
  - Blade components use domain prefixes
  - route names stay domain-qualified
- Update `README.md` domain documentation whenever a domain is added or materially reshaped.
