# Manufacturing

## Purpose

This page summarizes the manufacturing domain for readers. Detailed business rules live in [.kiro/steering/manufacturing.md](../.kiro/steering/manufacturing.md) and [.kiro/knowledge/](../.kiro/knowledge/).

## Core Domains

KM_Production covers:

- Production orders and operation execution
- Operation sequence versioning
- Inventory and stock movements
- Material consumption
- Serial numbers for in-house manufactured outputs
- Quality control and inspection results
- Documents attached to manufacturing records
- Procurement and purchase receiving
- Manufacturing intelligence and AI-assisted review

## Non-Negotiable Rules

- Every in-house manufactured component, subassembly, and finished product receives a unique serial number.
- Purchased materials do not receive the in-house unique serial number by default.
- Inventory quantities must never be modified directly.
- Every stock change must happen through a stock movement.
- Operation sequence changes require a new version.
- Existing production orders remain traceable to their original operation sequence version.
- Workshop finished output is inspected by default unless the operation sequence explicitly defines otherwise.

## Related Documentation

- [Manufacturing steering](../.kiro/steering/manufacturing.md)
- [Inventory knowledge](../.kiro/knowledge/inventory.md)
- [Production knowledge](../.kiro/knowledge/production.md)
- [Quality knowledge](../.kiro/knowledge/quality.md)
- [Procurement knowledge](../.kiro/knowledge/procurement.md)
- [Documents knowledge](../.kiro/knowledge/documents.md)
- [Product vision](vision/manufacturing-intelligence-platform.md)
