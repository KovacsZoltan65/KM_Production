# AGENTS.md

## Purpose

`AGENTS.md` is the root entry point for AI agents working on KM_Production.

KM_Production is a Laravel, Vue, Inertia, and MySQL Manufacturing Execution System for production workflows, inventory, traceability, quality control, procurement, documentation, and manufacturing intelligence.

## Start Here

Before significant work, read in this order:

1. [README.md](README.md)
2. [.kiro/index.md](.kiro/index.md)
3. Relevant files under [.kiro/steering/](.kiro/steering/)
4. Relevant architecture decisions under [.kiro/decisions/](.kiro/decisions/)
5. Relevant domain knowledge under [.kiro/knowledge/](.kiro/knowledge/)
6. Relevant procedures under [.kiro/playbooks/](.kiro/playbooks/)
7. Relevant quality gates under [.kiro/checklists/](.kiro/checklists/)
8. Relevant workflows under [.kiro/workflows/](.kiro/workflows/)
9. Relevant permanent memory under [.kiro/memory/](.kiro/memory/)

## Core Rules

- Follow the layered architecture: Controller -> Service -> Repository -> Model.
- Keep business logic out of controllers.
- Never modify inventory quantities directly; use stock movements.
- Preserve manufacturing traceability, serial numbers, operation sequence versions, audit logs, and permissions.
- Use shared Laravel JSON translation keys for backend and frontend text.
- Do not modify business logic unless explicitly requested.
- Prefer links to deeper documentation over duplicating guidance here.

## Documentation System

Project-specific rules, decisions, knowledge, playbooks, prompts, templates, checklists, workflows, and memory live under [.kiro/](.kiro/).

Reader-facing product documentation lives under [docs/](docs/).
