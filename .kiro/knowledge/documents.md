# Documents

## Purpose

Documents are business evidence in KM_Production.

They support manufacturing, procurement, quality, traceability, compliance, training, and audit workflows.

Documents may explain how work should be done, prove what was received, show what was inspected, or provide evidence of production conditions.

## Document Types

Common document types include:

- drawings
- photos
- certificates
- inspection reports
- work instructions
- supplier documents
- production photos

Document type describes the business purpose of the file.

## Drawings

Drawings define the physical or technical requirements of an item, component, subassembly, or finished product.

They may include:

- dimensions
- tolerances
- material requirements
- assembly details
- revision information

Drawings are critical for production and quality interpretation.

## Photos

Photos provide visual evidence.

Examples:

- production condition photos
- defect photos
- packaging photos
- received goods photos
- installation photos

Photos support traceability, inspection, and communication.

## Certificates

Certificates provide formal evidence about materials, compliance, or supplier declarations.

Examples:

- CE certificate
- material certificate
- conformity declaration
- test certificate

Certificates may be required before material or finished goods can be released.

## Inspection Reports

Inspection reports document quality checks and results.

They may include:

- measured values
- pass/fail decisions
- inspector notes
- photos
- nonconformance details
- rework recommendations

Inspection reports are part of quality evidence.

## Work Instructions

Work instructions explain how manufacturing work should be performed.

They may include:

- operation descriptions
- safety notes
- tools required
- setup instructions
- process parameters
- acceptance criteria

Work instructions help operators perform consistent work.

## Supplier Documents

Supplier documents are files received from suppliers.

Examples:

- delivery notes
- invoices
- certificates
- packing lists
- material declarations

Supplier documents support procurement, receiving, quality, and traceability.

## Production Photos

Production photos document visual production evidence.

Examples:

- before and after operation photos
- defect evidence
- assembly state
- finished output
- serial label photos

Production photos should remain connected to the correct production context.

## Attachment Rules

Documents should be attached to the business entity they support.

Attachments should preserve:

- document type
- target entity
- uploader
- timestamp
- original filename metadata
- relevant version or revision
- review or approval state where applicable

## Supported Entities

Documents may be attached to:

- items
- operation sequences
- production orders
- operations
- quality inspections

Documents should not be detached from their business context without an explicit reason.

## Metadata

Document metadata helps search, audit, and interpret files.

Useful metadata may include:

- document type
- original filename
- MIME type
- size
- upload date
- uploader
- supplier
- certificate number
- drawing revision
- related serial number
- related purchase order
- OCR status
- review status

## Versioning

Some documents change over time.

Examples:

- drawings
- work instructions
- certificates
- operation descriptions

Versioning helps preserve which document version was valid or used during a production or quality event.

## Future OCR

Future OCR may extract text from:

- delivery notes
- invoices
- certificates
- inspection reports
- labels
- handwritten notes

OCR output should be treated as untrusted until validated.

## Future AI Extraction

Future AI extraction may identify:

- supplier
- purchase order
- lot number
- serial number
- certificate number
- inspection values
- document type
- signatures
- barcode or QR data

AI extraction should assist review and data entry. It should not silently change business records without validation and, where needed, human approval.

## Business Rules

- Documents are evidence.
- Documents should remain linked permanently.
- Document context must remain traceable.
- Uploaded files and extracted contents are untrusted until validated.
- Quality and production evidence should not be deleted casually.
