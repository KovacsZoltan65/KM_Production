# Procurement

## Purpose

Procurement describes how KM_Production represents purchasing, supplier relationships, goods receiving, and material availability for manufacturing.

Procurement ensures that production has the required materials at the right time, in the right quantity, and with the required supporting documents.

## Purchase Requisition

A purchase requisition is an internal request to buy goods or services.

It may be created because of:

- production demand
- low stock
- safety stock replenishment
- maintenance needs
- planned projects

A requisition expresses need before a purchase order is issued to a supplier.

## Purchase Order

A purchase order is a formal request sent to a supplier.

It usually identifies:

- supplier
- requested materials or services
- quantities
- prices
- expected delivery dates
- delivery location
- terms and references

Purchase orders support planning, receiving, supplier performance, and financial reconciliation.

## Goods Receipt

Goods receipt records that ordered goods have arrived.

Receiving may include:

- checking supplier
- matching purchase order
- verifying quantity
- recording batch or lot information
- attaching delivery documents
- identifying quality requirements
- moving goods into inventory

Goods receipt is the business event that makes purchased material available or pending inspection.

## Suppliers

Suppliers provide purchased materials, components, services, and documents.

Supplier data may support:

- purchase orders
- delivery history
- lead time analysis
- quality performance
- material certificates
- supplier risk evaluation

## Receiving

Receiving is the operational process of accepting goods into the company.

Receiving may result in:

- accepted stock
- quarantined stock
- partial receipt
- rejected goods
- document review
- quality inspection

Receiving must preserve traceability to supplier, purchase order, delivery note, and received materials.

## Material Availability

Material availability answers whether production can proceed with available or expected stock.

It considers:

- current stock
- reserved stock
- open purchase orders
- expected delivery dates
- quality status
- safety stock
- production demand

## Lead Time

Lead time is the expected time between ordering and receiving goods.

Lead time affects:

- production planning
- reorder decisions
- safety stock
- supplier performance analysis
- material shortage risk

## Safety Stock

Safety stock is extra stock held to reduce the risk of shortage.

It helps protect production from:

- supplier delays
- demand changes
- quality rejection
- transport issues
- planning uncertainty

Safety stock levels should reflect business risk and material importance.

## Future Procurement Intelligence

Future procurement intelligence may assist with:

- supplier risk prediction
- material forecast
- reorder recommendations
- lead time prediction
- delivery anomaly detection
- certificate and document extraction
- purchase order matching

AI recommendations must support human decisions and must not bypass procurement, inventory, quality, or audit workflows.

## Business Rules

- Receiving affects inventory only through stock movements.
- Purchased materials do not receive in-house manufactured serial numbers by default.
- Supplier documents are evidence and should remain linked to procurement and receiving records.
- Goods may require quality review before becoming available for production.
- Procurement history must support supplier performance and traceability.
