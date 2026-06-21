# AGENTS.md

## Project

KM_Production

Manufacturing Execution System (MES) and Production Management platform built with:

- Laravel 13
- PHP 8.4+
- MySQL
- Inertia.js
- Vue 3
- PrimeVue 4
- @primeuix/themes
- Tailwind CSS 4
- Vite

The project is a production management system for manufacturing workflows, inventory, traceability, quality control, and documentation.

---

## Architecture

Always follow this layer order:

Controller
-> Service
-> Repository
-> Model

Rules:

- Do not place business logic in controllers.
- Controllers coordinate requests, authorization, validation, and responses.
- Services contain business rules and workflow orchestration.
- Repositories contain data access and query logic.
- Use interfaces for repositories.
- Use database transactions for critical business operations.

---

## Backend Conventions

Use:

- FormRequest validation
- Policy-based authorization
- Spatie Permission
- Spatie Activitylog
- Prettus Repository
- Explicit return types
- Enums where justified
- Small methods with clear names

Every important business action must be logged.

Examples:

- Production order created
- Operation started
- Operation completed
- Material consumed
- Stock movement created
- Quality inspection performed
- Scrap recorded
- Subassembly transferred

---

## Frontend Conventions

Use:

- Vue 3 Composition API
- Inertia
- PrimeVue components
- Reusable components
- Page components under `resources/js/Pages/...`
- Shared components under `resources/js/Components/...`

Avoid:

- jQuery
- Duplicated UI logic
- Oversized components
- Mixed business and presentation concerns in the same component

---

## Production Rules

The system is built for production management.

Requirements:

- Every in-house manufactured component, subassembly, and finished product receives a unique serial number.
- Purchased materials do not receive unique serial numbers.
- Serial number format:

```txt
PREFIX/YYYY/NNNN
```

Examples:

```txt
HEG/2026/0001
FES/2026/0007
AAA/2026/0001
```

`PREFIX` is the factory unit identifier.

---

## Operation Sequence Rules

- Manufacturing operations happen in a fixed order.
- Operation sequences are versioned.
- Changing the order or adding a new operation requires a new operation sequence/version.
- Existing production orders must remain traceable with their original operation sequence version.

---

## Inventory Rules

Inventory quantities must never be modified directly.

Every stock change must happen through a `stock_movements` record.

Movement types include:

- purchase_receive
- production_issue
- production_consume
- production_output
- transfer
- scrap
- correction

At any time, the system must know:

- Which warehouse/location contains stock
- Which material is present
- Which subassembly is present
- Which finished product is present
- How much quantity is available

---

## Quality Control

- By default, each workshop finished output must be inspected.
- Operation sequence steps have a field indicating whether inspection is required.
- Inspection results can be:

  - accepted
  - rejected
  - rework_required

---

## Documentation

Documents can be attached to:

- Items
- Operation sequences
- Production orders
- Specific operations
- Quality inspections

Supported document types:

- drawing
- operation_description
- work_note
- quality_report
- photo

---

## Permissions

CRUD permissions:

- create
- view
- update
- delete

Special permission:

- check

Professional roles must be handled separately from authorization roles.

For example, an employee can be:

- An admin user for authorization purposes
- A welder professionally

Do not mix professional roles with permission roles.

---

## Testing

Write tests for business-critical behavior.

Required areas:

- Serial number generation
- Stock movement
- Material consumption
- Operation sequence versioning
- Quality control
- Subassembly transfer
- Permission checks

---

## Prohibitions

- Do not modify business logic unless explicitly requested.
- Do not modify inventory quantities directly.
- Do not create large classes with mixed responsibilities.
- Do not mix professional roles with authorization roles.
- Do not put business logic in controllers.
- Do not duplicate UI or workflow logic.
