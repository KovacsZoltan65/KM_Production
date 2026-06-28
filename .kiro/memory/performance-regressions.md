# Performance Regressions

## Purpose

This permanent memory file records performance regressions, their causes, and prevention guidance.

## How to Record Entries

Record regressions that affect response time, query count, memory use, build time, frontend rendering, reports, dashboards, scheduling, manufacturing intelligence, or batch processing.

## Template

```md
## YYYY-MM-DD - Short title

Context:

Regression:

Cause:

Impact:

Prevention:

Future AI Guidance:

Related:
```

## Examples

```md
## 2026-06-28 - Documentation-only tasks should not run heavy application checks by default

Context: Documentation refactoring.

Regression: Running full application test suites for docs-only changes can slow feedback without increasing confidence.

Cause: Treating documentation edits like code changes.

Impact: Slower iteration and unnecessary local workload.

Prevention: For docs-only tasks, verify changed paths and references unless code behavior is affected.

Future AI Guidance: Match verification cost to change risk.

Related: `.kiro/checklists/documentation.md`
```

## Future AI Guidance

When performance knowledge becomes a stable engineering rule, link it from [.kiro/steering/performance-related guidance](../steering/) or [.kiro/checklists/performance.md](../checklists/performance.md).
