# AI Memory

## Purpose

AI memory is permanent organizational memory for KM_Production. It records durable lessons that should influence future work but do not belong as rules, decisions, domain knowledge, playbooks, prompts, templates, checklists, or workflows.

Use this index to choose the right memory file.

## How to Record Entries

Add dated entries with enough context for a future human or AI agent to understand the lesson without reading chat history.

Each entry should include:

- date
- context
- observation
- impact
- guidance
- related links

Do not record secrets, private user data, unverified speculation, or temporary task notes.

## Template

```md
## YYYY-MM-DD - Short title

Context:

Observation:

Impact:

Future AI Guidance:

Related:
```

## Examples

```md
## 2026-06-28 - Documentation belongs in the correct layer

Context: A documentation refactor created navigation across `.kiro/` and `docs/`.

Observation: Generic AI development concepts should be separated from KM_Production product documentation.

Impact: Future agents can avoid mixing product docs with AI framework docs.

Future AI Guidance: Use `.kiro/index.md` to choose the right documentation layer before editing.

Related: `.kiro/index.md`, `docs/architecture.md`
```

## Memory Files

- [Mistakes](mistakes.md)
- [Lessons learned](lessons-learned.md)
- [Architectural pitfalls](architectural-pitfalls.md)
- [AI hallucinations](ai-hallucinations.md)
- [Performance regressions](performance-regressions.md)
- [Breaking changes](breaking-changes.md)

## Future AI Guidance

Before completing significant work, consider whether the task revealed a durable lesson. If it did, add it to the most specific memory file and link the related steering, decision, knowledge, playbook, checklist, or workflow.
