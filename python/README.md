# Python AI Engine

## Purpose

This directory contains the local Python AI Engine proof of concept for KM_Production.

The current engine is intentionally minimal. It accepts JSON through stdin and returns JSON through stdout. It does not perform OCR, computer vision, external API calls, database access, or file processing.

## Current Scope

- Health-check task.
- Document classification stub using filename heuristics only.
- Optional OCR adapter task.
- JSON input and output only.
- No Laravel `.env` reads.
- No database access.
- No external dependencies.

## Run Manually

```bash
echo {"task":"health_check"} | python python/ai_engine.py
```

```bash
echo {"task":"document_classification","document":{"filename":"delivery_note.pdf"}} | python python/ai_engine.py
```

```bash
echo {"task":"document_ocr","document":{"filename":"sample.txt","path":"C:/path/to/sample.txt","mime_type":"text/plain"},"options":{"backend":"stub","max_text_bytes":20000}} | python python/ai_engine.py
```

## Boundaries

Laravel owns business rules, validation, permissions, database writes, and audit logging. Python owns only local processing and returns structured JSON for Laravel to validate.

The OCR adapter is currently a stub. It can read a limited amount of plain text for deterministic tests, but it does not perform real OCR.
