<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";

/** @typedef {{id: number, quantity: string|number, reserved_quantity: number, available_quantity: number, unit: string|null, item: {item_number: string, name: string}|null, location: {code: string}|null, item_batch: {batch_number: string}|null}} StockBalanceRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {StockBalanceRecord[]} data Az aktuális oldal készletegyenlegei.
 * @property {number} current_page Az aktuális oldalszám.
 * @property {number} per_page Az oldalankénti elemszám.
 * @property {number} total A teljes elemszám.
 * @property {number} last_page Az utolsó oldalszám.
 */
/**
 * Listaoldal szerveroldali szűrői.
 * @typedef {Object} PageFilters
 * @property {string} [search] A keresőkifejezés.
 * @property {number|string} [per_page] Az oldalankénti elemszám.
 * @property {string} [sort] A rendezett mező.
 * @property {'asc'|'desc'} [direction] A rendezés iránya.
 * @property {string|number|null} [status] Az állapotszűrő.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {PaginatedResult} records A(z) records bemeneti értéke.
 * @property {PageFilters} filters A(z) filters bemeneti értéke.
 */
/** @type {Props} */
defineProps({
    records: Object,
    filters: Object,
});

const number = (value) => Number(value || 0).toFixed(3);

const columns = [
    {
        field: "item.item_number",
        headerKey: "fields.item_number",
        sortable: false,
    },
    { field: "item.name", headerKey: "fields.item", sortable: false },
    { field: "location.code", headerKey: "fields.location", sortable: false },
    {
        field: "item_batch.batch_number",
        headerKey: "fields.batch",
        sortable: false,
        format: (row) => row.item_batch?.batch_number || "-",
    },
    {
        field: "quantity",
        headerKey: "fields.quantity",
        format: (row) => number(row.quantity),
    },
    {
        field: "reserved_quantity",
        headerKey: "fields.reserved",
        sortable: false,
        format: (row) => number(row.reserved_quantity),
    },
    {
        field: "available_quantity",
        headerKey: "fields.available",
        sortable: false,
        format: (row) => number(row.available_quantity),
    },
    { field: "unit", headerKey: "fields.unit", sortable: false },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="inventory.stock_balances.title"
        subtitle-key="inventory.stock_balances.subtitle"
        route-name="admin.inventory.stock-balances"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
    />
</template>
