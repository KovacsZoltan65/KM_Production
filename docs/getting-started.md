# Getting Started

## Purpose

This page orients developers and AI agents before they run or change KM_Production.

## Project Context

KM_Production is a Manufacturing Execution System. It manages production workflows, inventory, traceability, quality checks, documents, procurement, and manufacturing intelligence.

## Local Setup

Use the project-standard Laravel, PHP, MySQL, Node, and Vite setup. Keep environment-specific credentials outside documentation and source control.

Typical development flow:

1. Install PHP dependencies with Composer.
2. Install frontend dependencies with npm.
3. Configure `.env` for the local database and application URL.
4. Run migrations and seeders when a fresh dataset is needed.
5. Start the Laravel and Vite development servers.

## Quality Checks

Before committing backend changes, run:

```bash
php artisan test
vendor/bin/pint
vendor/bin/phpstan analyse
```

PHPStan uses Larastan at level 3 with an initial baseline for existing findings. New work should keep analysis green and reduce baseline entries when touching related code.

## Before Changing Code

Read:

- [AGENTS.md](../AGENTS.md)
- [AI documentation index](../.kiro/index.md)
- [Architecture](architecture.md)
- Relevant steering, decisions, knowledge, playbooks, checklists, workflows, and memory under `.kiro/`

## Related Documentation

- [Manufacturing domain](manufacturing.md)
- [Deployment](deployment.md)
- [API](api.md)
- [Product vision](vision/manufacturing-intelligence-platform.md)
