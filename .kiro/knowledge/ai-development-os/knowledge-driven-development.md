# Knowledge-Driven Development

> This concept is generic and may later become part of the standalone AI Development OS project.

Knowledge-driven development treats engineering knowledge as part of the system, not as an afterthought.

Code is essential, but code alone does not preserve intent. Teams also need durable knowledge about architecture, domain rules, tradeoffs, workflows, and quality expectations.

## Core Idea

The project should contain enough structured knowledge for a new contributor or AI agent to make better decisions without repeatedly asking for the same context.

## Benefits

- Faster onboarding.
- More consistent AI assistance.
- Less architectural drift.
- Better review quality.
- Clearer historical context.
- More repeatable engineering work.

## Practice

When a task reveals durable knowledge, update the appropriate layer:

- Rules go to steering.
- Tradeoffs go to decisions.
- Domain facts go to knowledge.
- Procedures go to playbooks.
- Reusable instructions go to prompts.
- Quality gates go to checklists.
- Lessons learned go to memory.

## KM_Production Placement

This document is preserved under `.kiro/knowledge/ai-development-os/` because it describes a reusable AI development practice rather than a manufacturing-specific concept.
