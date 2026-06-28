# Python AI Engine

## Purpose

This directory contains the local Python AI Engine proof of concept for KM_Production.

The current engine is intentionally minimal. It accepts JSON through stdin and returns JSON through stdout. It does not perform OCR, computer vision, external API calls, database access, or file processing.

## Current Scope

- Health-check task only.
- JSON input and output only.
- No Laravel `.env` reads.
- No database access.
- No external dependencies.

## Run Manually

```bash
echo {"task":"health_check"} | python python/ai_engine.py
```

## Boundaries

Laravel owns business rules, validation, permissions, database writes, and audit logging. Python owns only local processing and returns structured JSON for Laravel to validate.
