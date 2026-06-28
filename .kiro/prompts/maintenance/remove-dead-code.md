# Purpose

Reusable prompt for safely removing unused code.

# Context to Read First

AI agents should consult:

- `AGENTS.md`
- `.kiro/steering/`
- `.kiro/decisions/`
- `.kiro/knowledge/`
- `.kiro/playbooks/`

# Prompt Template

```txt
You are working on the KM_Production Laravel + Vue + Inertia MES project.

Identify and remove dead code related to: [SCOPE].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Verify references before removal. Check routes, controllers, services, repositories, policies, translations, Vue components, tests, config, jobs, events, and documentation.
Do not remove traceability, audit, inventory, quality, serial, or AI/OCR code unless explicitly confirmed.
```

# Required Checks

- All references are searched.
- Removed code is not used by routes, jobs, policies, tests, or frontend.
- No business history or traceability behavior is removed.
- Translations and permissions are cleaned only when safe.
- Tests are updated only if behavior is intentionally removed.

# Expected Output

Report removed files/code, evidence it was unused, tests run, and any uncertain references that were preserved.
