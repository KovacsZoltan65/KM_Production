# Purpose

Mandatory checklist for documentation impact review.

# Checklist

- [ ] README updated?
- [ ] AGENTS updated?
- [ ] Steering affected?
- [ ] Decision affected?
- [ ] Knowledge affected?
- [ ] Playbook affected?
- [ ] Prompt affected?
- [ ] Template affected?
- [ ] API documentation affected?
- [ ] Developer documentation affected?
- [ ] User-facing workflow documentation affected?
- [ ] Permanent memory affected?
- [ ] Translation keys documented where relevant?

# Common Mistakes

- Changing architecture without updating steering.
- Making architectural decisions without ADRs.
- Adding domain concepts without knowledge documentation.
- Changing implementation workflow without playbook updates.
- Forgetting API behavior documentation.
- Recording durable lessons in chat only instead of `.kiro/memory/`.

# Completion Criteria

- Documentation remains consistent with the implementation.
- Missing documentation is either added or explicitly marked as not applicable.
- Cross-layer references are added where they help future navigation.
