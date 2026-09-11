# KM_Production

KM_Production is a Manufacturing Execution System for managing production,
inventory, traceability, quality control, documentation, procurement, and
manufacturing intelligence. It connects manufacturing workflows with the records
needed to follow materials, operations and quality decisions.

## Getting Oriented

- [Project knowledge index](.kiro/index.md): documentation structure, domain
  decisions, implementation guidance and current planning.
- [Contribution guide](CONTRIBUTING.md): starting work, choosing a workflow,
  validation and review preparation.
- [Beginner user guide](docs/user-guides/kezdo-felhasznaloi-utmutato/README.md)
  and [first steps after installation](docs/user-guides/telepites-utani-elso-lepesek.md):
  using the manufacturing system (Hungarian).
- [Architecture](docs/architecture.md): how the application is organized.
  AI agents begin with [AGENTS.md](AGENTS.md).

## Technology

- Laravel 13
- PHP 8.4.1+ for the locked dependencies
- MySQL
- Inertia.js
- Vue 3
- PrimeVue 4
- Tailwind CSS 4
- Vite

Dependency requirements and scripts live in [composer.json](composer.json) and
[package.json](package.json); exact resolved versions are in their lockfiles.
The root Composer PHP constraint is `^8.3`, while locked Symfony packages
require PHP 8.4.1 or newer. Consult the lockfile for individual compatibility limits.

## Development

Use [Getting Started](docs/getting-started.md#local-setup) for setup orientation.
Configure the local `.env` before database operations: [.env.example](.env.example)
defaults to SQLite, so a MySQL environment needs explicit connection settings.
Keep credentials outside source control.

The project provides `composer setup` for dependency installation, environment
creation when absent, application key generation, migrations and frontend build.
It runs `migrate --force` against the configured database; inspect the script and
use it only for the intended local setup. It does not seed data; see
[Sample Data](docs/reference/sample-data.md) when a fresh dataset is needed.
After setup, `composer dev` starts the development processes, including Vite.
For the frontend alone, use `npm run dev`; `npm run build` builds production assets.

Choose validation from [Layered Quality Gates](docs/development/quality-gates.md)
and the [contribution guide](CONTRIBUTING.md). `composer qa:full` does not include
every project validation; applicable checks outside that command remain separate.
Testing procedures are linked below. Documentation changes follow the
[Documentation Checklist](.kiro/checklists/documentation.md) and
[language and readability guidance](.kiro/steering/coding-style.md#documentation-language-and-readability).

## Documentation

- Reference
    - [Sample Data](docs/reference/sample-data.md)
- [Kezdő felhasználói útmutató](docs/user-guides/kezdo-felhasznaloi-utmutato/README.md)
- [Telepítés utáni első lépések](docs/user-guides/telepites-utani-elso-lepesek.md)
- [Getting started](docs/getting-started.md)
- [Architecture](docs/architecture.md)
- [Domain Constitution](.kiro/steering/domain-constitution.md)
- [Planning Engine és MRP architektúra](.kiro/knowledge/planning-engine.md)
- [Domain terminológia](.kiro/knowledge/domain-terminology.md)
- [Material Requirements Planning Architecture ADR](.kiro/decisions/0006-material-requirements-planning-architecture.md)
- [Knowledge Graph architecture](docs/architecture/knowledge-graph.md)
- [Course Model Specification](docs/architecture/course-model.md)
- [Projektkonvenciók](docs/architecture/project-conventions.md)
- [Commitüzenet-konvenció](docs/project-management/commit-conventions.md)
- [Projektszintű Definition of Done](docs/project-management/definition-of-done.md)
- [Code review útmutató](docs/project-management/code-review-guide.md)
- [Hozzájárulási útmutató](CONTRIBUTING.md)
- [Manufacturing domain](docs/manufacturing.md)
- [Deployment](docs/deployment.md)
- [Frontend automatizált tesztelés](docs/frontend-testing.md)
- [E2E testing](docs/e2e-testing.md)
- [Frontend head- és lapcímkezelés](docs/frontend-head-management.md)
- [Backend statikus elemzés](docs/static-analysis.md)
- [Backend quality gate: SQLite és MySQL](docs/backend-quality-gate.md)
- [Üzleti cache-stratégia](docs/architecture/cache-strategy.md)
- [Cache-invalidation mátrix](docs/architecture/cache-invalidation-matrix.md)
- [Fejlesztői cache-útmutató](docs/development/caching.md)
- [Rétegezett quality gate-ek](docs/development/quality-gates.md)
- [API](docs/api.md)
- [Learning Center v1.0 specifikáció](docs/specifications/learning-center/README.md)
- [Product vision](docs/vision/manufacturing-intelligence-platform.md)

## Architecture Milestones

These dated milestones record project history. Current work and immediate
priorities are recorded in the [backlog](docs/project-management/backlog.md)
and [next actions](docs/project-management/next-actions.md).

| Date       | Milestone                  | Description                                                                               |
| ---------- | -------------------------- | ----------------------------------------------------------------------------------------- |
| 2026-07-04 | Learning Center Foundation | Initial Learning Center architecture, Knowledge Unit, Context Engine, project conventions |
| 2026-07-04 | Knowledge Layer Foundation | Knowledge Graph promoted to project-wide architecture                                     |
