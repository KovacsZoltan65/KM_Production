# Purpose

Reusable prompt for reviewing performance risks.

# Context to Read First

AI agents should consult:

- `AGENTS.md`
- `.kiro/steering/`
- `.kiro/decisions/`
- `.kiro/knowledge/`
- `.kiro/playbooks/`

# Prompt Template

```txt
You are reviewing KM_Production changes for performance.

Review: [FILES / BRANCH / DIFF].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files unless asked to fix findings.

Focus on N+1 queries, unbounded datasets, missing pagination, expensive dashboard/report queries, unnecessary relationships, frontend rendering cost, and long-running HTTP work.

Keep Controller -> Service -> Repository -> Model boundaries intact when recommending fixes.
```

# Required Checks

- Large lists use server-side pagination.
- Queries eager-load only necessary relationships.
- Filtering/searching/sorting are repository-owned.
- Reports and AI/OCR work are queued when long-running.
- Caching is safe and justified.
- Frontend does not load excessive data.

# Expected Output

Lead with performance findings ordered by impact, with file and line references. Include suggested fixes and tests or measurements needed.
