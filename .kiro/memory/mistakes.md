# Mistakes

## Purpose

This permanent memory file records mistakes that should not be repeated in KM_Production.

## How to Record Entries

Record a mistake when the project learns from an incorrect implementation, review finding, documentation drift, unsafe assumption, or repeated workflow failure.

Include what happened, why it mattered, how it was corrected, and what future agents should do differently.

## Template

```md
## YYYY-MM-DD - Short title

Context:

Mistake:

Impact:

Correction:

Future AI Guidance:

Related:
```

## Examples

```md
## 2026-06-28 - Avoid mixing product docs with AI framework concepts

Context: Documentation structure review.

Mistake: Generic AI development concepts were placed under `docs/concepts/`, which made them look like product documentation.

Impact: Readers could confuse KM_Production architecture with reusable AI Development OS ideas.

Correction: Move generic concepts into the AI documentation system and mark them as extraction candidates.

Future AI Guidance: Keep product docs in `docs/` and AI operating assets in `.kiro/`.

Related: `.kiro/index.md`
```

## Future AI Guidance

Check this file before refactoring documentation, architecture, inventory, production, quality, permissions, or AI workflows. If a mistake becomes a stable rule, promote it to steering or an ADR and link back here.
