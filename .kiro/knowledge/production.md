# Production

## Manufacturing Concepts

Production describes how KM_Production represents the work of transforming materials into manufactured components, subassemblies, and finished products.

Manufacturing work is organized around:

- production orders
- production tasks
- operation sequences
- work centers
- employees
- materials
- subassemblies
- finished products
- traceability records

## Production Orders

A production order represents planned or active manufacturing work.

It identifies:

- what will be produced
- target quantity
- required materials
- required operations
- assigned factory unit or work center
- production status
- traceability records

Production orders connect planning, execution, inventory, quality, and documentation.

## Production Tasks

Production tasks represent executable work inside a production order.

Tasks may correspond to:

- a manufacturing operation
- material preparation
- assembly
- inspection
- transfer
- completion or output recording

Tasks help track progress and define what workers must do next.

## Operation Sequences

An operation sequence is the ordered set of manufacturing steps required to produce an item.

Examples:

- cut material
- weld component
- paint part
- assemble subassembly
- inspect output
- package finished product

Operations must follow the defined sequence unless an authorized business workflow explicitly allows an exception.

## Operation Types

Operation types describe the nature of the manufacturing work.

Examples:

- cutting
- welding
- machining
- assembly
- painting
- inspection
- packaging
- transfer

Operation types help plan work, assign capabilities, define quality checks, and analyze production performance.

## Versioning

Manufacturing processes change over time.

Operation sequences are versioned so historical production orders remain connected to the exact process version used during execution.

When a process changes, a new version is created. Old production orders keep their original version.

## Material Consumption

Material consumption records which input materials were used during production.

Consumption connects:

- material
- quantity
- production order
- operation or task
- output item
- actor and timestamp

Material consumption is essential for inventory accuracy, costing, quality investigation, and traceability.

## Work Centers

A work center is a place or resource where manufacturing work happens.

Examples:

- welding station
- assembly line
- paint booth
- quality inspection bench
- packaging area

Work centers help schedule, assign, and measure production work.

## Employees

Employees perform production work, inspections, transfers, and approvals.

Professional roles describe work capabilities.

Examples:

- welder
- painter
- assembler
- quality inspector
- warehouse operator

Professional roles are not the same as authorization roles.

## Factory Units

Factory units identify organizational or physical production areas.

Factory unit identifiers may be used in serial number prefixes and production traceability.

Examples:

- `HEG`
- `FES`
- `AAA`

## Subassemblies

A subassembly is a manufactured intermediate product used inside a larger product.

Subassemblies may have:

- serial numbers
- production history
- consumed materials
- quality inspections
- documents
- stock location

Subassemblies must remain traceable because defects can affect final products.

## Finished Products

A finished product is the final manufactured output ready for stock, delivery, sale, or further business processing.

Finished products receive serial numbers and may require quality inspection before release.

## Traceability

Production traceability connects:

- production order
- operation sequence version
- tasks
- employees
- materials consumed
- stock movements
- serial numbers
- quality inspections
- documents
- output items

Traceability supports audit, warranty, service, compliance, and root cause analysis.

## Status Transitions

Production work moves through business states.

Examples:

- planned
- released
- in progress
- waiting for material
- waiting for inspection
- completed
- rejected
- cancelled

Status transitions must reflect real manufacturing progress and preserve history.

## Business Rules

- Operations follow a defined sequence.
- Production history must remain immutable.
- Used operation sequence versions must remain linked to their production orders.
- Material consumption must be recorded.
- Manufactured components, subassemblies, and finished products require serial numbers.
- Required inspections must be completed before accepted output is released.
