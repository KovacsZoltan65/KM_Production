<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";

/** @typedef {{id: number, name: string, permissions: string[], created_at: string|null}} RoleRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {RoleRecord[]} data Az aktuális oldal szerepkörei.
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
 * @property {{permissions: string[]}} options A kiosztható jogosultságnevek.
 */
/** @type {Props} */
defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: "name", headerKey: "fields.name" },
    {
        field: "permissions",
        headerKey: "fields.permissions",
        sortable: false,
        format: (record) => record.permissions?.length || 0,
    },
];

const fields = [
    { name: "name", labelKey: "fields.name", type: "text" },
    {
        name: "permissions",
        labelKey: "fields.permissions",
        type: "multiselect",
        options: "permissions",
        default: [],
    },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.roles.title"
        subtitle-key="master_data.roles.subtitle"
        route-name="admin.roles"
        create-label-key="master_data.roles.create"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
