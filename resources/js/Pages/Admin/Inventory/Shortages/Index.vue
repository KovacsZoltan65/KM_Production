<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";
import { notifyRequestError } from "@/Composables/useRequestError";

/** @typedef {{id: number, missing_quantity: string|number, unit: string, status: string, required_item: {item_number: string, name: string}|null, customer_order_item: {customer_order: {order_number: string}|null, production_orders: {order_number: string}[]}|null}} ShortageRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {ShortageRecord[]} data Az aktuális oldal anyaghiányai.
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

const toast = useToast();
const refreshing = ref(false);

const refreshRecords = () => {
    if (refreshing.value) {
        return;
    }

    router.reload({
        only: ["records"],
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            refreshing.value = true;
        },
        onError: (error) => {
            notifyRequestError(toast, error, {
                fallbackKey: "notifications.error.refresh_failed",
            });
        },
        onFinish: () => {
            refreshing.value = false;
        },
    });
};

const number = (value) => Number(value || 0).toFixed(3);

const columns = [
    {
        field: "customer_order_item.customer_order.order_number",
        headerKey: "fields.customer_order",
        sortable: false,
    },
    {
        field: "customer_order_item.production_orders",
        headerKey: "fields.production_order",
        sortable: false,
        format: (row) =>
            row.customer_order_item?.production_orders?.[0]?.order_number ||
            "-",
    },
    {
        field: "required_item.item_number",
        headerKey: "fields.required_item_number",
        sortable: false,
    },
    {
        field: "required_item.name",
        headerKey: "fields.required_item",
        sortable: false,
    },
    {
        field: "missing_quantity",
        headerKey: "fields.missing",
        format: (row) => number(row.missing_quantity),
    },
    { field: "unit", headerKey: "fields.unit", sortable: false },
    { field: "status", headerKey: "fields.status" },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="inventory.shortages.title"
        subtitle-key="inventory.shortages.subtitle"
        route-name="admin.inventory.shortages"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
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
