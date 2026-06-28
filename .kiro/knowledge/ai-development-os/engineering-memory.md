# Engineering Memory

> This concept is generic and may later become part of the standalone AI Development OS project.

Engineering memory is the durable record of what a project has learned.

It includes decisions, lessons, recurring issues, constraints, useful patterns, and warnings that should influence future work.

## Why It Matters

Software teams often lose context when work moves from one issue, contributor, or tool to another. AI agents can also repeat mistakes when important knowledge exists only in chat history.

Engineering memory gives the project a place to keep useful learning.

## What Belongs in Memory

- Lessons from completed tasks.
- Patterns that should be reused.
- Mistakes that should not be repeated.
- Operational discoveries.
- Review feedback that applies beyond one pull request.
- Agent-specific observations that improve future work.

## What Does Not Belong in Memory

- Temporary task notes.
- Secrets or credentials.
- Private user data.
- Speculation that has not been validated.
- Information already captured clearly in steering or decisions.

## Principle

Projects should improve after every completed task.

## KM_Production Placement

This document is preserved under `.kiro/knowledge/ai-development-os/` because it describes a reusable AI development concept. KM_Production-specific memory lives in [.kiro/memory/](../../memory/).
