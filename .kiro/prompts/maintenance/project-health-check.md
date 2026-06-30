# KM_PRODUCTION — PROJECT HEALTH CHECK MODE

## Purpose

This is the master health-check prompt for the whole KM_Production project.

Use it to inspect the repository, run relevant checks, fix safe issues, and produce one final project health report.

Do not blindly rewrite the project. A health check should preserve existing behavior, surface risks clearly, and improve reliability only where changes are safe and well understood.

## Operating Rules

- Preserve existing behavior.
- Do not modify unrelated files.
- Do not weaken tests.
- Do not delete meaningful tests.
- Do not bypass authorization.
- Do not bypass validation.
- Do not change business logic unless it is clearly buggy.
- Prefer small, safe fixes.
- Record reusable lessons only when genuinely useful.

## Required Context

Before acting, read:

- `AGENTS.md`
- `.kiro/index.md`
- `.kiro/steering/`
- `.kiro/decisions/`
- `.kiro/knowledge/`
- `.kiro/playbooks/`
- `.kiro/checklists/`
- `.kiro/workflows/`
- `.kiro/prompts/maintenance/test-maintenance.md`
- `docs/vision/`
- `docs/ai/`

## Execution Flow

Execute these sections in order.

### 1. Repository State Check

Inspect:

- `git status`
- current branch
- recent commits
- uncommitted changes
- risky local state

Report any local changes before modifying files. Do not overwrite or revert user work unless explicitly requested.

### 2. Architecture Health Check

Inspect:

- Controllers
- Services
- Repositories
- Models
- Policies
- FormRequests
- Jobs
- AI services

Check:

- business logic is not in controllers
- query logic is not in controllers
- repository/service boundaries are respected
- policies exist for protected actions
- transactions exist where required

### 3. Test Health Check

Follow:

- `.kiro/prompts/maintenance/test-maintenance.md`

Run:

- `php artisan test`
- frontend tests only if configured in `package.json`
- AI-related tests

Do not skip failing tests. Fix safe test failures by addressing their real cause.

### 4. AI Infrastructure Health Check

Inspect:

- `python/`
- `config/ai.php`
- `PythonAiEngineService`
- `ProcessDocumentJob`
- OCR adapter
- OCR backend plugin system
- telemetry model/service
- `docs/ai/`

Verify:

- Python communicates JSON only
- Python does not access the Laravel database
- OCR remains optional
- missing OCR backend fails safely
- telemetry does not store raw OCR text
- AI contracts remain stable

### 5. Security Health Check

Inspect:

- authorization
- policies
- validation
- file upload assumptions
- secrets
- logging
- AI/OCR security boundaries

Check:

- no secrets are logged
- uploaded files are treated as untrusted
- frontend visibility is not treated as authorization
- AI cannot directly mutate business data

### 6. Performance Health Check

Inspect:

- large queries
- N+1 risks
- missing pagination
- queue usage
- AI process timeout
- telemetry storage growth risk

Fix only safe obvious issues. Otherwise, report risks.

### 7. Documentation Health Check

Inspect:

- `README.md`
- `AGENTS.md`
- `docs/`
- `.kiro/`

Check:

- stale references
- broken navigation
- outdated AI docs
- missing links after changes
- memory updates only when valuable

### 8. Dependency Health Check

Inspect:

- `composer.json`
- `package.json`
- Python dependency files if present

Do not upgrade dependencies automatically unless the upgrade is explicitly safe and requested. Report outdated or risky dependencies if discovered.

### 9. Final Verification

After safe fixes, run relevant checks again:

- `php artisan test`
- frontend tests if configured
- AI tests
- `vendor/bin/pint`
- PHPStan/Larastan if configured

## Fix Policy

### Safe Fixes

Safe fixes may include:

- broken tests caused by stale factories
- invalid test setup
- missing casts
- missing test helpers
- stale documentation links
- harmless formatting
- missing deterministic AI test coverage

### Unsafe Fixes

Report these instead of changing them unless explicitly requested:

- large architecture rewrites
- business workflow changes
- permission model changes
- destructive migrations
- dependency upgrades with risk
- AI contract breaking changes

## Final Report Format

The final response must include:

- Branch
- Repository state
- Backend tests run and result
- Frontend tests run and result
- AI tests run and result
- Pint/static analysis result
- Files created
- Files modified
- Production code changed
- Tests added/modified
- Documentation updated
- Issues fixed
- Risks found
- Issues intentionally not fixed
- Memory updates
- Recommended next actions

## Completion Criteria

The project health check is complete only when:

- all safe checks have been run
- all safe fixes have been applied
- tests are green or remaining failures are clearly explained
- no assertions were weakened
- no meaningful tests were deleted
- final report is complete

## Principle

A project health check should not merely make the repository look clean; it should make the project safer, easier to maintain, and more reliable for future human and AI developers.
