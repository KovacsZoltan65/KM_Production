# Architectural Pitfalls

## Purpose

This permanent memory file records architecture mistakes and design traps that future work should avoid.

## How to Record Entries

Record a pitfall when an implementation or proposal risks violating KM_Production architecture, traceability, domain boundaries, or documentation structure.

## Template

```md
## YYYY-MM-DD - Short title

Context:

Pitfall:

Risk:

Preferred Approach:

Future AI Guidance:

Related:
```

## Examples

```md
## 2026-06-28 - Documentation layer collision

Context: The request asked for both `docs/architecture.md` and `docs/architecture/`.

Pitfall: On Windows, a file and directory cannot share the same path stem in the same folder.

Risk: Trying to create both would fail or fragment navigation.

Preferred Approach: Use `docs/architecture.md` as the reader-facing page and place AI framework concepts under `.kiro/`.

Future AI Guidance: Resolve path collisions explicitly and report the chosen convention.

Related: `docs/architecture.md`, `.kiro/index.md`
```

## Future AI Guidance

Read this file when changing architecture, module boundaries, documentation hierarchy, or manufacturing traceability flows.
