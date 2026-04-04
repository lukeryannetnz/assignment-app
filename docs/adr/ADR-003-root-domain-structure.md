# ADR-003: Root-Level Domain Structure

- Status: Accepted
- Date: 2026-04-04
- Supersedes: `docs/adr/ADR-002-domain-structure.md`

## Context

ADR-002 established a domain-first repository structure while keeping Laravel's default top-level folders (`app`, `resources`, `database`, `tests`) as the primary filesystem anchors.

That decision improved domain ownership substantially, but the repository is still organized framework-first at the root. A feature spans multiple root folders:

- `domains/<Domain>/...`
- `resources/domains/<domain>/...`
- `tests/Domain/<Domain>/...`
- `database/<type>/<Domain>/...`

This means a single domain still requires navigation across several top-level Laravel directories. If we want domains to become the primary organizational unit of the repository rather than just the primary namespace inside Laravel folders, the root structure must change as well.

## Decision

Adopt a root-level domain structure where each business domain owns its application code, tests, UI, routes, and persistence artifacts from a single top-level location.

Laravel remains the runtime shell, but the repository is organized domain-first at the root.

## Structure Summary

- Business-owned artifacts live under `domains/<Domain>/...`.
- Each domain owns its `app`, `resources`, `database`, and `tests` subtrees.
- `domains/Foundation` is reserved for framework composition and repository architecture enforcement.
- Laravel-required root entrypoints may remain in conventional locations, but only as thin wrappers into the domain tree.
- Naming remains domain-qualified across namespaces, route names, views, and Blade components.
- Catch-all business buckets such as `Shared` or `Common` are not part of this structure.

## Consequences

### Positive

- each domain becomes a self-contained unit at the repository root
- feature navigation becomes simpler because code, tests, UI, and persistence live together
- domains are easier to extract into packages or bounded modules later

### Tradeoffs

- Laravel-default conventions no longer match the repository layout
- more custom composition code is required for autoloading, Blade registration, migrations, tests, and assets
- refactoring risk is materially higher than the current ADR-002 layout
- onboarding is less familiar for Laravel developers expecting conventional roots

## Alternatives Considered

1. Keep ADR-002 as-is
- Lowest-risk option and still strongly domain-oriented, but domains remain split across Laravel root folders.

2. Introduce a `src/Domains` subtree only for PHP code
- Simpler than full root-domain ownership, but still leaves tests, UI, and database artifacts split across the repository.

3. Convert each domain into a package
- Strong modularity, but too heavy for the current application phase and would add more packaging overhead than value right now.
