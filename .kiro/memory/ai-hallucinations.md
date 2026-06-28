# AI Hallucinations

## Purpose

This permanent memory file records cases where AI agents invented, assumed, or overstated project facts.

## How to Record Entries

Record hallucinations when an AI agent references files, APIs, business rules, packages, tests, routes, permissions, or workflows that are not actually present or verified.

## Template

```md
## YYYY-MM-DD - Short title

Context:

Hallucination:

Verified Fact:

Impact:

Future AI Guidance:

Related:
```

## Examples

```md
## 2026-06-28 - Verify documentation paths before referencing them

Context: Documentation navigation work.

Hallucination: Assuming a requested top-level `templates/` directory exists without checking.

Verified Fact: Only `.kiro/templates/` existed in the workspace.

Impact: A move operation would be unnecessary and misleading.

Future AI Guidance: Use file search before reporting moved or skipped documentation assets.

Related: `.kiro/templates/`, `.kiro/index.md`
```

## Future AI Guidance

Prefer verified local context over memory. If a fact matters to implementation or documentation, inspect the file or command output before relying on it.
