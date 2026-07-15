<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";

/** @typedef {{id: number, name: string, guard_name: string}} PermissionRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {PermissionRecord[]} data Az aktuális oldal jogosultságai.
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
    { field: "name", headerKey: "fields.name" },
    { field: "guard_name", headerKey: "fields.guard" },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.permissions.title"
        subtitle-key="master_data.permissions.subtitle"
        route-name="admin.permissions"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
    />
</template>
