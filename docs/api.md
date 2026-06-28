# API

## Purpose

This page is the reader-facing API documentation entry point for KM_Production.

## Current Guidance

API behavior must follow the same domain rules as the Inertia application:

- authorize protected actions
- validate input with FormRequest classes
- route business operations through services
- preserve audit logs and traceability
- return predictable validation and authorization responses
- avoid exposing data outside the caller's permissions

Detailed API conventions live in [.kiro/steering/api.md](../.kiro/steering/api.md).

## Related Documentation

- [Architecture](architecture.md)
- [Backend steering](../.kiro/steering/backend.md)
- [Security steering](../.kiro/steering/security.md)
- [Documentation checklist](../.kiro/checklists/documentation.md)
