<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";
import { notifyRequestError } from "@/Composables/useRequestError";
import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import { ref } from "vue";

/** @typedef {{id: number, item_number: string, name: string, item_type: string, unit: string, width: string|number|null, length: string|number|null, thickness: string|number|null, diameter: string|number|null, requires_serial_number: boolean, is_active: boolean}} ItemRecord */
/** @typedef {{label: string, value: string}} ItemTypeOption */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {ItemRecord[]} data Az aktuális oldal cikkei.
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
 * @property {ItemTypeOption[]} itemTypes A választható cikktípusok.
 */
/** @type {Props} */
defineProps({
    records: Object,
    filters: Object,
    itemTypes: Array,
});

const toast = useToast();
const refreshing = ref(false);

const refreshRecords = () => {
    if (refreshing.value) return;

    router.reload({
        only: ["records"],
        preserveState: true,
        preserveScroll: true,
        onStart: () => (refreshing.value = true),
        onError: (error) =>
            notifyRequestError(toast, error, {
                fallbackKey: "notifications.error.refresh_failed",
            }),
        onFinish: () => (refreshing.value = false),
    });
};

/* OSZLOPOK */
const columns = [
    { field: "item_number", headerKey: "fields.item_number" },
    { field: "name", headerKey: "fields.name" },
    {
        field: "item_type",
        headerKey: "fields.type",
        format: (record) => trans(`enum.item_type.${record.item_type}`),
    },
    { field: "unit", headerKey: "fields.unit" },
    {
        field: "requires_serial_number",
        headerKey: "fields.serial",
        format: (record) =>
            record.requires_serial_number ? trans("common.yes") : trans("common.no"),
    },
    { field: "is_active", headerKey: "fields.status", type: "status" },
];

/* MEZŐK */
const fields = [
    // A cikk azonosítóját jelző mező, amely rejtett.
    { name: "id", type: "hidden" },
    {
        name: "item_number",
        labelKey: "fields.item_number",
        type: "text",
        required: true,
        immutableOnEdit: true,
        generateCode: {
            type: "item",
            parameters: { item_type: "item_type" },
        },
        icon: "hashtag",
    },
    // A cikk nevét jelző mező, amely kötelező.
    { name: "name", labelKey: "fields.name", type: "text", icon: "tag" },

    // A cikk típusát jelző mező, amely a "itemTypes" opciók közül választható, és alapértelmezett értéke "purchased_material".
    {
        name: "item_type",
        labelKey: "fields.type",
        type: "select",
        options: "itemTypes",
        enumKey: "enum.item_type",
        default: "purchased_material",
        layoutGroup: "group_01",
    },
    // A cikk mértékegységét jelző mező, amely kötelező.
    {
        name: "unit",
        labelKey: "fields.unit",
        type: "unit",
        required: true,
        layoutGroup: "group_01",
    },

    // A cikk szélességét jelző mező, amely a "dimensions" elrendezési csoportba tartozik.
    {
        name: "width",
        labelKey: "fields.width",
        type: "number",
        layoutGroup: "dimensions",
    },
    // A cikk hosszát jelző mező, amely a "dimensions" elrendezési csoportba tartozik.
    {
        name: "length",
        labelKey: "fields.length",
        type: "number",
        layoutGroup: "dimensions",
    },
    // A cikk vastagságát jelző mező, amely a "dimensions" elrendezési csoportba tartozik.
    {
        name: "thickness",
        labelKey: "fields.thickness",
        type: "number",
        layoutGroup: "dimensions",
    },
    // A cikk átmérőjét jelző mező, amely a "dimensions" elrendezési csoportba tartozik.
    {
        name: "diameter",
        labelKey: "fields.diameter",
        type: "number",
        layoutGroup: "dimensions",
    },
    // A cikktípus mezőhöz tartozó checkbox mező, amely jelzi, hogy a cikkhez sorozatszám szükséges-e.
    {
        name: "requires_serial_number",
        labelKey: "fields.requires_serial_number",
        type: "checkbox",
        default: false,
    },
    // A cikk aktív státuszát jelző checkbox mező, amely alapértelmezett értéke true.
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
        title-key="master_data.items.title"
        subtitle-key="master_data.items.subtitle"
        route-name="admin.items"
        create-label-key="master_data.items.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
        :options="{ itemTypes }"
    >
        <template #header-actions>
            <Button
                type="button"
                :label="trans('actions.refresh')"
                icon="pi pi-refresh"
                severity="secondary"
                outlined
                :loading="refreshing"
                :disabled="refreshing"
                data-test="refresh-records"
                @click="refreshRecords"
            />
        </template>
    </AdminCrudPage>
</template>
