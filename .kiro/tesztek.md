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
