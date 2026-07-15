<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";

/** @typedef {{id: number, code: string, name: string, description: string|null, is_active: boolean}} ProfessionalRoleRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {ProfessionalRoleRecord[]} data Az aktuális oldal szakmai szerepkörei.
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
    { field: "is_active", headerKey: "fields.status", type: "status" },
];

const fields = [
    { name: "code", labelKey: "fields.code", type: "text" },
    { name: "name", labelKey: "fields.name", type: "text" },
    { name: "description", labelKey: "fields.description", type: "textarea" },
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
        title-key="master_data.professional_roles.title"
        subtitle-key="master_data.professional_roles.subtitle"
        route-name="admin.professional-roles"
        create-label-key="master_data.professional_roles.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
