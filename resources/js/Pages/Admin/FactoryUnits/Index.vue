<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";

/** @typedef {{id: number, code: string, name: string, description: string|null, daily_capacity_minutes: number, shift_count: number, is_active: boolean}} FactoryUnitRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {FactoryUnitRecord[]} data Az aktuális oldal gyártóegységei.
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
    { field: "daily_capacity_minutes", headerKey: "fields.daily_capacity" },
    { field: "shift_count", headerKey: "fields.shifts" },
    { field: "is_active", headerKey: "fields.status", type: "status" },
];

const fields = [
    { name: "code", labelKey: "fields.code", type: "text" },
    { name: "name", labelKey: "fields.name", type: "text" },
    { name: "description", labelKey: "fields.description", type: "textarea" },
    {
        name: "daily_capacity_minutes",
        labelKey: "fields.daily_capacity_minutes",
        type: "number",
    },
    {
        name: "shift_count",
        labelKey: "fields.shift_count",
        type: "number",
        default: 1,
    },
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
        title-key="master_data.factory_units.title"
        subtitle-key="master_data.factory_units.subtitle"
        route-name="admin.factory-units"
        create-label-key="master_data.factory_units.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
