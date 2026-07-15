<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";

/** @typedef {{id: number, code: string, name: string, tax_number: string|null, email: string|null, phone: string|null, billing_address: string|null, shipping_address: string|null, notes: string|null, is_active: boolean}} CustomerRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {CustomerRecord[]} data Az aktuális oldal vevőrekordjai.
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

const columns = [
    { field: "code", headerKey: "fields.code" },
    { field: "name", headerKey: "fields.name" },
    { field: "tax_number", headerKey: "fields.tax_number" },
    { field: "email", headerKey: "fields.email" },
    { field: "phone", headerKey: "fields.phone" },
    { field: "is_active", headerKey: "fields.status", type: "status" },
];

const fields = [
    { name: "code", labelKey: "fields.code", type: "text" },
    { name: "name", labelKey: "fields.name", type: "text" },
    { name: "tax_number", labelKey: "fields.tax_number", type: "text" },
    { name: "email", labelKey: "fields.email", type: "email" },
    { name: "phone", labelKey: "fields.phone", type: "text" },
    {
        name: "billing_address",
        labelKey: "fields.billing_address",
        type: "textarea",
    },
    {
        name: "shipping_address",
        labelKey: "fields.shipping_address",
        type: "textarea",
    },
    { name: "notes", labelKey: "fields.notes", type: "textarea" },
    {
        name: "is_active",
        labelKey: "status.active",
        type: "checkbox",
        default: true,
    },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.customers.title"
        subtitle-key="master_data.customers.subtitle"
        route-name="admin.customers"
        create-label-key="master_data.customers.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
