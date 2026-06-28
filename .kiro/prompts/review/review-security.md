# Purpose

Reusable prompt for reviewing security risks in KM_Production changes.

# Context to Read First

AI agents should consult:

- `AGENTS.md`
- `.kiro/steering/`
- `.kiro/decisions/`
- `.kiro/knowledge/`
- `.kiro/playbooks/`

# Prompt Template

```txt
You are reviewing KM_Production changes for security.

Review: [FILES / BRANCH / DIFF].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files unless asked to fix findings.

Focus on backend-enforced security, policy authorization, FormRequest validation, secret handling, file upload safety, AI/OCR trust boundaries, activity logging, and inventory/traceability protections.

Check Controller -> Service -> Repository -> Model responsibilities.
```

# Required Checks

- No frontend-only authorization.
- Modifying actions are authorized.
- Restricted reads are authorized.
- Inputs and uploads are validated.
- Secrets are not logged or committed.
- Stack traces are not exposed.
- AI/OCR outputs are treated as untrusted.
- Stock is not mutated directly.

# Expected Output

Lead with security findings ordered by severity, with file and line references. Include open questions, residual risk, and tests reviewed or not run.
