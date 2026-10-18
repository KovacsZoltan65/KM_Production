# KM_Production — Merlin Recovery Checkpoint 001

**Checkpoint:** `MERLIN_RECOVERY_001`

**Date:** 2026-10-18

**Purpose:** Restore development context and safely resume work after interruption, lost conversation, failed Codex session or development incident.

## 1. Repository identity

Repository: `https://github.com/KovacsZoltan65/KM_Production`

Local repository: `C:\wamp64\www\km\KM_Production`

Expected branch: `feature/merlin-candidate`

Verified checkpoint commit:

`fdbad40a0a337e80a82b96b3004a77e9cf6dbe22`

Commit message:

`docs(merlin): accept execution lifecycle architecture`

At checkpoint creation, local HEAD, tracking HEAD and remote HEAD matched. The working tree and staging area were clean.

This describes the historical checkpoint. Do not assume it remains the current repository state.

## 2. Project context

KM_Production is a manufacturing execution system.

Technology:

- Laravel 13
- Vue 3
- Inertia
- PrimeVue
- MySQL
- PHP and JavaScript tests

Project conventions:

- Code, database identifiers and APIs: English.
- Documentation, ADRs and technical explanations: Hungarian.
- User interface: localized.
- Preserve existing project quality gates.

## 3. Merlin architecture

Merlin is an AI-assisted investigation and advisory subsystem.

Its responsibilities:

- Investigate business problems identified by KM_Production.
- Use explicitly authorized tools.
- Produce evidence-based recommendations.
- Preserve execution and decision history.

Its boundaries:

- Merlin does not receive unrestricted database access.
- Merlin does not independently execute procurement, inventory or production actions.
- KM_Production controls Merlin execution.
- The historical requesting user does not provide permanent authorization.

Effective permission:

`User Permission ∩ AI Permission ∩ Case Context`

Business decisions and execution remain under human and authorized application control.

## 4. Completed milestones

### Slice 1A — Foundation

Committed MerlinRun persistence, statuses, database constraints, initial audit structure and tests.

### Slice 1B.1 — Trusted admission infrastructure

Committed canonical request handling, idempotency, request fingerprinting, trusted authorization and admission controls.

### Slice 1B.2 — Trusted investigation start

Commit:

`0b12ad0f903bffc3255043a5f073c0cb0b1c20a7`

Implemented internal trusted investigation admission.

Includes eligible case verification, authorized replay, transactional starting records and collision handling.

Does not include Merlin worker execution.

### ADR 0018 — Execution Lifecycle architecture

Commit:

`fdbad40a0a337e80a82b96b3004a77e9cf6dbe22`

File:

`.kiro/decisions/0018-merlin-execution-lifecycle.md`

Status: `ACCEPTED`

Accepted architectural direction:

- Single claim and worker fencing.
- Immutable execution deadline.
- Conditional lifecycle transitions.
- Atomic execution audit.
- Fresh authorization and eligibility verification.
- Bounded execution attempts.
- Controlled recovery and uncertain-commit reconciliation.
- No automatic worker takeover or execution resume in V1.
- Paid inference remains disabled.

Accepted terminal classification principle:

- Uncertain authoritative state or unavailable evaluation → `FAILED`.
- Demonstrably resolved shortage → `CANCELLED`.
- Demonstrably invalidated investigation basis → `CANCELLED`.

Neither outcome modifies the Problem Case or authorizes business actions.

## 5. Important unresolved work

Before L1 implementation:

- Exact lifecycle command and transition contracts.
- Terminal reason codes.
- Persistence schema, constraints and bounds.
- Clock and deadline representation.
- Command authorization.
- Execution audit event and evidence contracts.

Before L1 acceptance:

- Correct or explicitly verify Merlin test coverage in the quality-gate selection.

Before worker execution:

- Dispatch and pending deadlines.
- Recovery ownership.
- Queue timeout and visibility alignment.
- Operational recovery verification.
- Relevant real connection-loss testing.

Before paid inference:

- Provider approval.
- Monetary ceiling.
- Cost accounting and enforcement.

Additional outstanding task:

- Align the earlier investigation specification with the accepted ADR 0018 classification principle, under separate authorization.

Do not silently close any OPEN decision.

## 6. Mandatory recovery procedure

You are Codex operating in recovery mode.

**READ-ONLY DIAGNOSTICS FIRST.**

Do not assume that the current branch, worktree or remote matches this checkpoint.

### Step A — Identify the environment

Run appropriate read-only commands:

```powershell
git rev-parse --show-toplevel
git branch --show-current
git rev-parse HEAD
git status --short
git diff --cached --name-only
git remote -v
```

Verify the checkpoint commit exists:

```powershell
git cat-file -t fdbad40a0a337e80a82b96b3004a77e9cf6dbe22
git show --stat --oneline fdbad40a0a337e80a82b96b3004a77e9cf6dbe22
```

### Step B — Inspect branch relationships

Read-only inspection:

```powershell
git branch -avv
git log --oneline --decorate -12
git merge-base HEAD fdbad40a0a337e80a82b96b3004a77e9cf6dbe22
```

Determine whether:

- Current HEAD equals the checkpoint.
- Current HEAD is a descendant of the checkpoint.
- The checkpoint is ahead of current HEAD.
- The histories have diverged.

Do not automatically assume that a newer commit is defective.

Do not fetch, checkout, reset, rebase, merge, cherry-pick or restore files without separate authorization.

### Step C — Verify architecture

Read:

- ADR 0017.
- ADR 0018.
- Merlin investigation specification.
- MerlinRun model and status enum.
- Existing trusted admission implementation.
- Relevant tests and quality-gate configuration.

Determine which components are actually implemented.

Do not infer runtime capabilities merely because they appear in an accepted ADR.

### Step D — Classify recovery situation

Report one of:

`CHECKPOINT_MATCH`

Current state matches the recorded checkpoint.

`CHECKPOINT_DESCENDANT`

Development has legitimately advanced beyond the checkpoint.

`CHECKPOINT_BEHIND`

Current branch does not yet contain the checkpoint.

`CHECKPOINT_DIVERGED`

Current history differs from the checkpoint lineage.

`CHECKPOINT_DIRTY`

Uncommitted changes require preservation and investigation. Also report the underlying history relationship where determinable.

`CHECKPOINT_UNVERIFIABLE`

Required repository evidence is missing or cannot be reliably checked.

### Step E — Propose recovery

Provide the safest proposed next action based on verified evidence.

Priorities:

1. Preserve all existing work.
2. Avoid destructive Git operations.
3. Preserve valid newer commits.
4. Verify the integrity of the checkpoint.
5. Recover development context.
6. Resume only after user approval.

Do not execute the recovery plan.

## 7. Forbidden automatic actions

Never automatically run:

```powershell
git reset --hard
git clean -fd
git push --force
git rebase
git merge
git cherry-pick
```

Do not delete or overwrite local files.

Do not modify the database.

Do not run migrations or seeders.

Do not commit or push.

Do not begin L1 implementation.

Do not alter ADR acceptance status.

## 8. Required recovery report

Produce:

1. Repository identity.
2. Current branch and HEAD.
3. Checkpoint availability and commit verification.
4. Working tree and staging inventory.
5. Current relationship to checkpoint.
6. Existing Merlin implementation inventory.
7. Accepted architectural contracts.
8. Remaining OPEN decisions.
9. Risks to existing work.
10. Recommended recovery or continuation plan.

Final result:

`MERLIN_RECOVERY_001_DIAGNOSTIC_COMPLETE`

or

`MERLIN_RECOVERY_001_BLOCKED`

STOP and wait for explicit user authorization before making changes.
