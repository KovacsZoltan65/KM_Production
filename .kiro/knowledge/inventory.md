# Inventory

## Purpose

Inventory knowledge describes how KM_Production represents materials, subassemblies, finished products, quantities, storage, and stock history.

Inventory answers these business questions:

- What is in stock?
- Where is it stored?
- How much is available?
- What is reserved for production or orders?
- Why did the quantity change?
- Which serial, batch, or item instance is affected?

## Inventory Concepts

Inventory is the controlled record of physical goods held by the company.

Goods may include:

- purchased materials
- consumables
- in-house manufactured components
- subassemblies
- finished products
- scrapped or quarantined items

Inventory must support manufacturing traceability, procurement receiving, material consumption, transfers, quality workflows, and corrections.

## Warehouse

A warehouse is a high-level storage area.

Examples:

- main raw material warehouse
- production workshop
- finished goods warehouse
- quarantine area
- scrap area

Warehouses help identify the broad physical place where stock is held.

## Location

A location is a more specific place inside or related to a warehouse.

Examples:

- shelf
- bin
- pallet position
- workstation
- inspection area

Locations make stock easier to find and support traceability during production, transfer, receiving, and quality workflows.

## Stock

Stock is the quantity of an item currently held in a warehouse or location.

Stock may represent:

- a material quantity
- a subassembly quantity
- a finished product quantity
- a specific serial-numbered instance
- a batch or lot quantity

Stock must always be explainable from stock movement history.

## Reserved Stock

Reserved stock is inventory committed to a future or active business purpose.

Examples:

- material reserved for a production order
- finished goods reserved for a customer shipment
- stock reserved for quality review

Reserved stock is not freely available for other work.

## Available Stock

Available stock is the quantity that can be used, issued, transferred, or allocated.

Conceptually:

```txt
available stock = physical stock - reserved stock - blocked stock
```

Available stock must reflect business restrictions such as quality status, reservations, and storage location.

## Item Batches

An item batch groups stock that shares common origin or production information.

Examples:

- supplier lot
- received batch
- production batch
- material certificate batch

Batches support traceability when individual serial numbers are not required.

## Item Instances

An item instance is a specific physical unit that must be tracked individually.

Manufactured components, subassemblies, and finished products may become item instances when they receive serial numbers.

Item instances help answer:

- where the unit is now
- which production order created it
- which materials were consumed
- which inspections were performed
- which documents are linked

## Serial Numbers

Serial numbers uniquely identify manufactured components, subassemblies, and finished products.

Purchased materials do not receive in-house manufactured serial numbers by default.

Serial numbers support warranty, service, root cause analysis, quality history, and production traceability.

## Movement Types

Stock movement types describe why stock changed.

Common movement types:

- purchase receive
- production issue
- production consume
- production output
- transfer
- scrap
- correction

Movement types must reflect the business event that caused the inventory change.

## Reservation

Reservation sets aside stock for a specific purpose.

Reservations prevent the same stock from being promised or consumed by multiple workflows.

Examples:

- reserving material for a production order
- reserving components for assembly
- reserving finished goods for delivery

## Consumption

Consumption records material used during production.

Consumption connects input materials to production work and output items.

Material consumption is critical for traceability, cost analysis, and root cause investigation.

## Transfer

Transfer moves stock from one warehouse or location to another.

Examples:

- raw material warehouse to workshop
- workshop to quality inspection
- inspection to finished goods warehouse
- usable stock to quarantine

Transfers must preserve item identity, quantity, and traceability.

## Scrap

Scrap records stock that is no longer usable for normal production or sale.

Scrap may result from:

- production defects
- quality rejection
- damage
- expired material
- process waste

Scrap must remain traceable because it affects cost, quality, and process improvement.

## Correction

Correction records an authorized inventory adjustment.

Corrections may be needed after:

- counting differences
- data entry mistakes
- damaged stock discovery
- reconciliation

Corrections must have a clear business reason and audit trail.

## Business Rules

- Inventory is event driven.
- `StockMovement` is the source of truth.
- Inventory is never edited directly.
- Every quantity change must be explainable.
- Stock must always be tied to a warehouse or location.
- Traceability must identify what changed, why it changed, when it changed, and who caused the change.
