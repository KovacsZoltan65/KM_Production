<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";
import { notifyRequestError } from "@/Composables/useRequestError";
import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import { ref } from "vue";

/** @typedef {{id: number, item_number: string, name: string, unit: string, label: string}} ItemOption */
/** @typedef {{id: number, code: string, name: string, label: string}} SupplierOption */
/** @typedef {{id: number, item_id: number, supplier_id: number, supplier_item_code: string|null, purchase_unit: string, conversion_factor: string|number, minimum_order_quantity: string|number|null, order_multiple: string|number|null, unit_price: string|number|null, currency: string|null, lead_time_days: number|null, priority: number, is_preferred: boolean, is_approved: boolean, is_active: boolean, valid_from: string|null, valid_until: string|null, item: ItemOption, supplier: SupplierOption}} ItemSupplierRecord */
/** @typedef {{data: ItemSupplierRecord[], current_page: number, per_page: number, total: number, last_page: number}} PaginatedResult */
/** @typedef {{search?: string, per_page?: number|string, sort?: string, direction?: 'asc'|'desc', item_id?: number|null, supplier_id?: number|null}} PageFilters */
/**
 * @typedef {Object} Props
 * @property {PaginatedResult} records
 * @property {PageFilters} filters
 * @property {ItemOption[]} itemOptions
 * @property {SupplierOption[]} supplierOptions
 */
/** @type {Props} */
defineProps({
    records: Object,
    filters: Object,
    itemOptions: Array,
    supplierOptions: Array,
});

const toast = useToast();
const refreshing = ref(false);
const yesNo = (value) => (value ? trans("common.yes") : trans("common.no"));
const optionalNumber = (value) => value ?? "—";
const formattedPrice = (record) =>
    record.unit_price === null
        ? "—"
        : `${record.unit_price} ${record.currency || ""}`.trim();

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

const columns = [
    {
        field: "item.name",
        sortField: "item_id",
        headerKey: "fields.item",
        format: (record) => `${record.item.item_number} - ${record.item.name}`,
    },
    {
        field: "supplier.name",
        sortField: "supplier_id",
        headerKey: "fields.supplier",
        format: (record) => `${record.supplier.code} - ${record.supplier.name}`,
    },
    {
        field: "supplier_item_code",
        headerKey: "procurement.sources.fields.supplier_item_code",
    },
    {
        field: "purchase_unit",
        headerKey: "procurement.sources.fields.purchase_unit",
    },
    {
        field: "minimum_order_quantity",
        headerKey: "procurement.sources.fields.minimum_order_quantity_short",
        format: (record) => optionalNumber(record.minimum_order_quantity),
    },
    {
        field: "order_multiple",
        headerKey: "procurement.sources.fields.order_multiple",
        format: (record) => optionalNumber(record.order_multiple),
    },
    {
        field: "lead_time_days",
        headerKey: "procurement.sources.fields.lead_time_days_short",
        format: (record) => optionalNumber(record.lead_time_days),
    },
    {
        field: "unit_price",
        headerKey: "procurement.sources.fields.unit_price",
        format: formattedPrice,
    },
    { field: "priority", headerKey: "procurement.sources.fields.priority" },
    {
        field: "is_preferred",
        headerKey: "procurement.sources.fields.is_preferred",
        format: (record) => yesNo(record.is_preferred),
    },
    {
        field: "is_approved",
        headerKey: "procurement.sources.fields.is_approved",
        format: (record) => yesNo(record.is_approved),
    },
    { field: "is_active", headerKey: "status.active", type: "status" },
];

const fields = [
    {
        name: "item_id",
        labelKey: "fields.item",
        type: "select",
        options: "itemOptions",
        required: true,
    },
    {
        name: "supplier_id",
        labelKey: "fields.supplier",
        type: "select",
        options: "supplierOptions",
        required: true,
    },
    {
        name: "supplier_item_code",
        labelKey: "procurement.sources.fields.supplier_item_code",
        type: "text",
    },
    {
        name: "purchase_unit",
        labelKey: "procurement.sources.fields.purchase_unit",
        type: "unit",
        required: true,
        layoutGroup: "unit_conversion",
    },
    {
        name: "conversion_factor",
        labelKey: "procurement.sources.fields.conversion_factor",
        type: "number",
        min: 0.000001,
        step: 0.000001,
        default: 1,
        required: true,
        layoutGroup: "unit_conversion",
    },
    {
        name: "minimum_order_quantity",
        labelKey: "procurement.sources.fields.minimum_order_quantity",
        type: "number",
        min: 0,
        step: 0.001,
        layoutGroup: "ordering",
    },
    {
        name: "order_multiple",
        labelKey: "procurement.sources.fields.order_multiple",
        type: "number",
        min: 0.001,
        step: 0.001,
        layoutGroup: "ordering",
    },
    {
        name: "lead_time_days",
        labelKey: "procurement.sources.fields.lead_time_days",
        type: "number",
        min: 0,
        step: 1,
        layoutGroup: "ordering",
    },
    {
        name: "unit_price",
        labelKey: "procurement.sources.fields.unit_price",
        type: "number",
        min: 0,
        step: 0.0001,
        layoutGroup: "price",
    },
    {
        name: "currency",
        labelKey: "procurement.sources.fields.currency",
        type: "text",
        layoutGroup: "price",
    },
    {
        name: "priority",
        labelKey: "procurement.sources.fields.priority",
        type: "number",
        min: 1,
        max: 9999,
        step: 1,
        default: 100,
    },
    {
        name: "is_preferred",
        labelKey: "procurement.sources.fields.is_preferred",
        type: "checkbox",
        default: false,
        layoutGroup: "states",
    },
    {
        name: "is_approved",
        labelKey: "procurement.sources.fields.is_approved",
        type: "checkbox",
        default: false,
        layoutGroup: "states",
    },
    {
        name: "is_active",
        labelKey: "status.active",
        type: "checkbox",
        default: true,
        layoutGroup: "states",
    },
    {
        name: "valid_from",
        labelKey: "procurement.sources.fields.valid_from",
        type: "date",
        layoutGroup: "validity",
    },
    {
        name: "valid_until",
        labelKey: "procurement.sources.fields.valid_until",
        type: "date",
        layoutGroup: "validity",
    },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="procurement.sources.title"
        subtitle-key="procurement.sources.subtitle"
        route-name="admin.item-suppliers"
        create-label-key="procurement.sources.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
        :options="{ itemOptions, supplierOptions }"
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
