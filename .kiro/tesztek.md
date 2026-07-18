# HAJNALHÉJ — TEST MAINTENANCE MODE (EXECUTION / AUTOFIX)

## Context

Laravel + Vue 3 + Inertia + PrimeVue projekt.

A projekt már tartalmaz:

- Pest backend teszteket
- Vitest frontend teszteket

A feladat NEM audit dokument írás.

A feladat:
👉 a teljes tesztkészlet **futtatása, javítása, bővítése és zöldre hozása**

---

## HARD RULES (kötelező)

- DO NOT only describe problems — ALWAYS FIX THEM
- DO NOT skip failing tests
- DO NOT weaken assertions to make tests pass
- DO NOT delete meaningful tests
- DO NOT change business logic unless it is clearly buggy

---

## PRIMARY OBJECTIVE

Ensure:

✅ All backend tests PASS
✅ All frontend tests PASS
✅ Missing critical tests are CREATED
✅ Test setup is STABLE and REPRODUCIBLE

---

## STEP 1 — Discover project state

Analyze:

- tests/Feature
- tests/Unit
- resources/js/**/**/_.test._
- resources/js/**/**/_.spec._
- package.json
- phpunit.xml
- pest.php
- vite.config.\*
- composer.json

Build a mental model of:

- existing coverage
- missing areas
- test conventions
- naming patterns

---

## STEP 2 — Run backend tests

Execute:

```bash
php artisan test
```

## STEP 3 — Run frontend tests

Inspect `package.json` first and identify:

- the configured package manager
- the Vitest test script
- coverage-related scripts
- required frontend test setup files

Install dependencies only if they are missing. Respect the existing lockfile:

- `package-lock.json` → npm
- `pnpm-lock.yaml` → pnpm
- `yarn.lock` → Yarn

Run the complete frontend test suite in non-interactive, single-run mode.

Use the existing `package.json` script whenever available, for example:

```bash
npm run test -- --run
```

````markdown
## FINAL VERIFICATION

After all fixes and new tests are complete, run the full verification sequence:

```bash
php artisan test
npm run test -- --run
npm run build
```
````
