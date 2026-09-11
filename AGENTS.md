# AGENTS.md

## Purpose

`AGENTS.md` is the root entry point for AI agents working on KM_Production.

KM_Production is a Laravel, Vue, Inertia, and MySQL Manufacturing Execution System for production workflows, inventory, traceability, quality control, procurement, documentation, and manufacturing intelligence.

## Start Here

Before significant work, use this order to select relevant context. Read the
applicable documents, not every file in each directory:

1. [README.md](README.md)
2. [.kiro/index.md](.kiro/index.md)
3. Relevant files under [.kiro/steering/](.kiro/steering/)
4. Relevant architecture decisions under [.kiro/decisions/](.kiro/decisions/)
5. Relevant domain knowledge under [.kiro/knowledge/](.kiro/knowledge/)
6. The applicable [workflow](.kiro/index.md#workflows-and-maintenance),
   [playbooks](.kiro/playbooks/) and [checklists](.kiro/checklists/)
7. [Definition of Done](docs/project-management/definition-of-done.md) for
   completion and [Layered Quality Gates](docs/development/quality-gates.md)
   for validation scope
8. Relevant lessons from [Memory](.kiro/memory/index.md)

Then inspect the affected implementation, tests and configuration before
performing the authorized task. For current work and priorities, follow the
index's [planning navigation](.kiro/index.md#current-work-and-historical-evidence).

## Core Rules

- Follow the layered architecture: Controller -> Service -> Repository -> Model.
- Keep business logic out of controllers.
- Never modify inventory quantities directly; use stock movements.
- Preserve manufacturing traceability, serial numbers, operation sequence versions, audit logs, and permissions.
- Use shared Laravel JSON translation keys for backend and frontend text.
- Do not modify business logic unless explicitly requested.
- Prefer links to deeper documentation over duplicating guidance here.

The [Domain Constitution](.kiro/steering/domain-constitution.md), applicable
[ADRs](.kiro/decisions/) and [Coding Style Guidance](.kiro/steering/coding-style.md)
provide the detailed domain and implementation rules.

A suspected bug does not authorize changing business rules. A feature request
does not authorize inventing domain rules, and refactoring must preserve
observable behavior. Use the applicable [workflow](.kiro/index.md#workflows-and-maintenance)
to identify the decision boundary. If the required business decision is unclear
or outside scope, report it before changing that behavior; continue independent,
authorized work.

## Documentation System

Project-specific rules, decisions, knowledge, playbooks, prompts, templates, checklists, workflows, and memory live under [.kiro/](.kiro/).

Reader-facing product documentation lives under [docs/](docs/).

All project documentation must follow the language and readability rules in
[Coding Style Guidance](.kiro/steering/coding-style.md#documentation-language-and-readability)
and the applicable [Documentation Checklist](.kiro/checklists/documentation.md).

## Authorization and Worktree Safety

This file owns agent authorization boundaries, applied to the current user/task
scope. A workflow, prompt, checklist or successful check does not expand that
authorization. Commit, push, pull request creation or update, merge, release and
deployment each require explicit user authorization.

Inspect Git state before editing and preserve pre-existing changes and untracked
files. Do not overwrite, delete, restore, stage or format unrelated work to obtain
a clean state. Report overlapping target changes and preserve them; clarify an
uncertain overlap before editing it. Destructive worktree cleanup requires a
separate explicit request and authorization. The applicable
[workflow](.kiro/index.md#workflows-and-maintenance) supplies the detailed procedure.

## Git and Commits

Commit messages and AI-agent commit rules are defined in
[Commit Message Convention](docs/project-management/commit-conventions.md).
Agents must use targeted staging and may commit or push only with explicit user
authorization.

## Pull Requests and Review

Agents may create or update a pull request only with explicit user
authorization. They must use the
[pull request template](.github/pull_request_template.md), follow the
[code review guide](docs/project-management/code-review-guide.md), report only
checks that actually ran, and inspect the complete branch diff. Agents must not
claim approval or merge with unresolved blockers. Enabling auto-merge or changing
branch protection and required checks requires explicit authorization. Apply the
[Before Commit](.kiro/checklists/before-commit.md) and
[Before Merge](.kiro/checklists/before-merge.md) checklists when those actions
are authorized.

## Definition of Done

Before reporting a task complete, agents must apply the
[project Definition of Done](docs/project-management/definition-of-done.md).
They must identify the relevant change types, record concrete evidence, explain
checks not run, and must not mark work `done` while a relevant blocker or
unverified requirement remains.
