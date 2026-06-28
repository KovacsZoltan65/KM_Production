# AI Documentation Index

## Purpose

This is the navigation entry for AI agents working on KM_Production. Start at [AGENTS.md](../AGENTS.md), then use this index to load only the documentation layers relevant to the task.

## Layer Diagram

```txt
AGENTS.md
↓
Steering
↓
Architecture Decisions
↓
Knowledge
↓
Playbooks
↓
Prompts
↓
Templates
↓
Checklists
↓
Workflows
↓
Memory
```

## Layers

- [AGENTS.md](../AGENTS.md): root entry point and non-negotiable operating rules.
- [Steering](steering/): stable project rules for architecture, backend, frontend, manufacturing, testing, security, API, and AI.
- [Architecture Decisions](decisions/): recorded tradeoffs and decisions that explain why the system works this way.
- [Knowledge](knowledge/): durable domain knowledge for manufacturing, production, inventory, quality, procurement, documents, and AI.
- [Playbooks](playbooks/): repeatable implementation procedures.
- [Prompts](prompts/): reusable task prompts for generation, review, maintenance, testing, and AI work.
- [Templates](templates/): reusable documentation templates for the AI development system.
- [Checklists](checklists/): quality gates for commits, modules, security, performance, releases, and documentation.
- [Workflows](workflows/): end-to-end procedures for feature development, bug fixes, hotfixes, refactoring, releases, and AI-native development.
- [Memory](memory/): permanent organizational memory for mistakes, lessons, pitfalls, hallucinations, performance regressions, and breaking changes.

## Cross-Reference Guidance

- If a task changes stable rules, update [Steering](steering/).
- If a task records a tradeoff, update [Architecture Decisions](decisions/).
- If a task clarifies manufacturing behavior, update [Knowledge](knowledge/).
- If a task changes a repeatable procedure, update [Playbooks](playbooks/).
- If a task improves reusable AI instructions, update [Prompts](prompts/).
- If a task creates a reusable file pattern, update [Templates](templates/).
- If a task adds a quality gate, update [Checklists](checklists/).
- If a task changes an end-to-end process, update [Workflows](workflows/).
- If a task reveals durable learning, update [Memory](memory/).

## Reader-Facing Documentation

Product documentation lives in [docs/](../docs/). Use it for human-readable guides, deployment notes, API notes, architecture overview, manufacturing overview, and product vision.
