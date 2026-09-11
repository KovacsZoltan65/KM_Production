# Project Documentation Index

## Purpose

This is the map of project knowledge for developers and AI agents working on
KM_Production. [README](../README.md) introduces the manufacturing system;
[CONTRIBUTING](../CONTRIBUTING.md) guides contribution work. AI agents start at
[AGENTS.md](../AGENTS.md). Use the layers relevant to the task.

## Authority by Topic

Use the document that owns the question. Directory order is not a universal
policy hierarchy, and this index does not redefine the linked rules.

| Question                                                   | Primary source                                                                            |
| ---------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| What may an AI agent do?                                   | [AGENTS.md](../AGENTS.md), applied to the current user/task scope.                        |
| Which domain principles and architecture decisions apply?  | [Domain Constitution](steering/domain-constitution.md) and applicable [ADRs](decisions/). |
| How should code and documentation be written?              | [Coding Style Guidance](steering/coding-style.md) and applicable steering.                |
| When is work complete, and how are check results reported? | [Definition of Done](../docs/project-management/definition-of-done.md).                   |
| Which validation scope is appropriate?                     | [Layered Quality Gates](../docs/development/quality-gates.md).                            |
| How should tests be designed?                              | [Testing Steering](steering/testing.md).                                                  |
| What does a backlog status mean?                           | [Backlog conventions](../docs/project-management/backlog-conventions.md).                 |
| How is work carried out?                                   | The applicable workflow and playbook, within the authorized scope.                        |

If sources genuinely conflict, report the contradiction and the affected decision
before relying on it. Do not silently choose a rule based only on file location.
Procedures, prompts and checklists do not grant authorization.

## Layers

- [AGENTS.md](../AGENTS.md): agent entry point, reading expectations and authorization boundaries.
- [Steering](steering/): stable project rules for architecture, backend, frontend, manufacturing, testing, security, API, and AI.
- [Architecture Decisions](decisions/): recorded tradeoffs and decisions that explain why the system works this way.
- [Knowledge](knowledge/): durable domain knowledge for manufacturing, production, inventory, quality, procurement, documents, and AI.
- [Playbooks](playbooks/): repeatable implementation procedures.
- [Prompts](prompts/): reusable task prompts for generation, review, maintenance, testing, and AI work.
- [Templates](templates/): reusable documentation templates for the AI development system.
- [Checklists](checklists/): practical verification lists for commits, modules, security, performance, releases, and documentation; completion and validation policy remain in the sources above.
- [Workflows](workflows/): end-to-end procedures for feature development, bug fixes, hotfixes, refactoring, releases, and AI-native development.
- [Memory](memory/index.md): permanent organizational memory for mistakes, lessons, pitfalls, hallucinations, performance regressions, and breaking changes.
- [Project management](../docs/project-management/): current planning and contribution governance.
- [Historical audits](../docs/audits/): dated observations and evidence.
- [User guides](../docs/user-guides/) and [specifications](../docs/specifications/): product usage and specified behavior; a specification alone does not prove implementation.

## Cross-Reference Guidance

When documentation updates are within the task scope, choose the owning layer
below. Apply the [Documentation Checklist](checklists/documentation.md) and
[readability rules](steering/coding-style.md#documentation-language-and-readability):
explain what and why simply before the technical how. Update affected material
only; finding a related document does not authorize changing it.

- If a task changes stable rules, update [Steering](steering/).
- If a task records a tradeoff, update [Architecture Decisions](decisions/).
- If a task clarifies manufacturing behavior, update [Knowledge](knowledge/).
- If a task changes a repeatable procedure, update [Playbooks](playbooks/).
- If a task improves reusable AI instructions, update [Prompts](prompts/).
- If a task creates a reusable file pattern, update [Templates](templates/).
- If a task adds a quality gate, update [Checklists](checklists/).
- If a task changes an end-to-end process, update [Workflows](workflows/).
- If a task reveals durable learning, update [Memory](memory/).

## Workflows and Maintenance

- [Feature Development](workflows/feature-development.md): introduce authorized new behavior.
- [Bug Fix](workflows/bug-fix.md): diagnose and correct behavior within the requested scope.
- [Refactoring](workflows/refactoring.md): improve internal structure while preserving observable behavior.
- [Playbooks](playbooks/): implementation procedures for the affected component.
- [Test Maintenance](prompts/maintenance/test-maintenance.md): diagnose tests and make authorized repairs.
- [Project Health Check](prompts/maintenance/project-health-check.md): assess the requested areas, with repair scope established separately.
- [Release](workflows/release.md), [Hotfix](workflows/hotfix.md) and
  [Deployment](../docs/deployment.md): use when the task covers those operations;
  their instructions do not authorize the actions.

## Validation and Contribution Preparation

- [Layered Quality Gates](../docs/development/quality-gates.md) and its
  [checklist](checklists/quality-gates.md): select proportionate validation and
  identify separate applicable checks outside the selected command.
- [Testing Steering](steering/testing.md): durable test design rules; execution
  procedures are in [backend](../docs/backend-quality-gate.md),
  [frontend](../docs/frontend-testing.md), [E2E](../docs/e2e-testing.md) and
  [static analysis](../docs/static-analysis.md).
- [Definition of Done](../docs/project-management/definition-of-done.md):
  applicability, evidence, check results and completion.
- [Before Commit](checklists/before-commit.md), [commit conventions](../docs/project-management/commit-conventions.md),
  [Before Merge](checklists/before-merge.md) and [Code Review Guide](../docs/project-management/code-review-guide.md):
  preparation and review for authorized contribution actions.
- [Documentation Checklist](checklists/documentation.md): content, navigation,
  language and documentation verification.

## Current Work and Historical Evidence

- [Backlog conventions](../docs/project-management/backlog-conventions.md)
  define work status and record structure.
- [Backlog](../docs/project-management/backlog.md) is the current work inventory
  and status record, including known discrepancies in completion evidence.
- [Next actions](../docs/project-management/next-actions.md) highlights immediate
  actionable priorities and their prerequisites. Absence from this list does not
  mean a task is `done`.
- [Audits](../docs/audits/) preserve dated findings and results. They do not
  override current steering, ADRs, governance, implementation evidence or planning
  state, and do not establish a successful check for today's change.

## Domain and Planning Foundations

These links describe principles and decisions. ADR acceptance does not prove
implementation or completion. Check current implementation and evidence when
assessing delivery; the [backlog](../docs/project-management/backlog.md) records
known state discrepancies, including the status label in ADR 0016.

- [Domain Constitution](steering/domain-constitution.md): durable principles for modeling real manufacturing events, separating planning from execution, time, uncertainty and traceability.
- [Planning Engine and MRP architecture](knowledge/planning-engine.md): dated assessment, component boundaries, Material Requirements Planning (MRP) scope, roadmap and example.
- [Domain terminology](knowledge/domain-terminology.md): the required vocabulary for Demand, Requirement, Supply, Shortage, Proposal, Pegging and procurement.
- [Material Requirements Planning Architecture](decisions/0006-material-requirements-planning-architecture.md): planning driven by material requirements.
- [Item Supplier / Procurement Source](decisions/0007-item-supplier-procurement-source.md): procurement relationships, terms and lifecycle between items and suppliers.
- [Supply Proposal](decisions/0008-supply-proposal.md): auditable proposals and decisions between planning and execution.
- [MRP Foundation Hardening](decisions/0008-5-mrp-foundation-hardening.md): production lineage, time semantics, snapshot boundaries and approval conditions.
- [Material Requirement Netting](decisions/0009-material-requirement-netting.md): matching requirements over time against stock on hand and firm incoming supply.
- [Requirement Pegging](decisions/0010-requirement-pegging.md): tracing requirement coverage to specific stock balances and firm purchase order items.
- [Purchase Requisition Consolidation](decisions/0011-purchase-requisition-consolidation.md): grouping approved purchase proposals into Draft Purchase Requisitions while retaining source traceability.
- [Supplier Selection](decisions/0012-supplier-selection.md): manually selecting a supplier eligible for all items of a Draft Purchase Requisition.
- [Replenishment Strategies](decisions/0013-replenishment-strategies.md): deriving requested quantities from planned quantities, minimum order quantities and order multiples.
- [0014 Purchase Requisition Execution Readiness](decisions/0014-purchase-requisition-execution-readiness.md): evaluating current supplier, source, replenishment, quantity and lineage conditions before execution without changing data.
- [0015 Purchase Order Generation / Execution Hardening](decisions/0015-purchase-order-generation.md): converting a ready Approved Purchase Requisition into a Draft Purchase Order with locking, idempotency and immutable snapshots.
- [0016 Purchase Order Dispatch / Supplier Acknowledgement](decisions/0016-purchase-order-dispatch-supplier-acknowledgement.md): preserving dispatch attempts and supplier responses separately from ordering, MRP, goods receipt, inventory and financial lifecycles.

## Reader-Facing Documentation

Product documentation lives in [docs/](../docs/). Use it for human-readable guides, deployment notes, API notes, architecture overview, manufacturing overview, and product vision.
