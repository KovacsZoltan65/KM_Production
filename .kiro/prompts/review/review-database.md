# Purpose

Reusable prompt for reviewing database, migration, and data integrity risks.

# Context to Read First

AI agents should consult:

- `AGENTS.md`
- `.kiro/steering/`
- `.kiro/decisions/`
- `.kiro/knowledge/`
- `.kiro/playbooks/`

# Prompt Template

```txt
You are reviewing KM_Production database-related changes.

Review: [FILES / BRANCH / DIFF].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files unless asked to fix findings.

Check migrations, foreign keys, nullable fields, indexes, enum values, traceability, stock movements, serial numbers, quality history, and transaction safety.

Ensure Controller -> Service -> Repository -> Model responsibilities remain clean.
```

# Required Checks

- Schema changes preserve traceability.
- Foreign keys and indexes are appropriate.
- Nullable fields have business reasons.
- No direct stock quantity mutation is introduced.
- Serial uniqueness and history are preserved.
- Migrations are reversible where practical.
- Critical writes are transactional.

# Expected Output

Lead with database findings ordered by severity, with file and line references. Include migration risks, data migration concerns, and test gaps.
