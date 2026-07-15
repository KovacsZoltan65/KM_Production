<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";

/** @typedef {{id: number, factory_unit_id: number|null, code: string, name: string, location_type: string, description: string|null, is_active: boolean, factory_unit: {name: string}|null}} LocationRecord */
/**
 * Gyártóegység választóeleme.
 * @typedef {Object} FactoryUnitOption
 * @property {number} id A gyártóegység azonosítója.
 * @property {string} name A gyártóegység neve.
 * @property {string} code A gyártóegység kódja.
 */
/**
 * Helytípus választóeleme.
 * @typedef {Object} LocationTypeOption
 * @property {string} label A helytípus felirata.
 * @property {string} value A helytípus értéke.
 */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {LocationRecord[]} data Az aktuális oldal raktárhelyei.
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
 * @property {{factoryUnits: FactoryUnitOption[], locationTypes: LocationTypeOption[]}} options A szerkesztőmezők választási lehetőségei.
 */
/** @type {Props} */
defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: "code", headerKey: "fields.code" },
    { field: "name", headerKey: "fields.name" },
    { field: "location_type", headerKey: "fields.type" },
    {
        field: "factoryUnit.name",
        headerKey: "fields.factory_unit",
        sortable: false,
        format: (record) => record.factory_unit?.name || "-",
    },
    { field: "is_active", headerKey: "fields.status", type: "status" },
];

const fields = [
    {
        name: "factory_unit_id",
        labelKey: "fields.factory_unit",
        type: "select",
        options: "factoryUnits",
        optionLabel: "name",
        optionValue: "id",
    },
    { name: "code", labelKey: "fields.code", type: "text" },
    { name: "name", labelKey: "fields.name", type: "text" },
    {
        name: "location_type",
        labelKey: "fields.location_type",
        type: "select",
        options: "locationTypes",
        enumKey: "enum.location_type",
    },
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
        title-key="master_data.locations.title"
        subtitle-key="master_data.locations.subtitle"
        route-name="admin.locations"
        create-label-key="master_data.locations.create"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
