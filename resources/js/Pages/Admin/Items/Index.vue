<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";
import { trans } from "laravel-vue-i18n";

defineProps({
    records: Object,
    filters: Object,
    itemTypes: Array,
});

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
            record.requires_serial_number
                ? trans("common.yes")
                : trans("common.no"),
    },
    { field: "is_active", headerKey: "fields.status", type: "status" },
];

const fields = [
    { name: "item_number", labelKey: "fields.item_number", type: "text" },
    { name: "name", labelKey: "fields.name", type: "text" },
    {
        name: "item_type",
        labelKey: "fields.type",
        type: "select",
        options: "itemTypes",
        enumKey: "enum.item_type",
        default: "purchased_material",
    },
    { name: "unit", labelKey: "fields.unit", type: "unit", required: true },
    {
        name: "width",
        labelKey: "fields.width",
        type: "number",
        layoutGroup: "dimensions",
    },
    {
        name: "length",
        labelKey: "fields.length",
        type: "number",
        layoutGroup: "dimensions",
    },
    {
        name: "thickness",
        labelKey: "fields.thickness",
        type: "number",
        layoutGroup: "dimensions",
    },
    {
        name: "diameter",
        labelKey: "fields.diameter",
        type: "number",
        layoutGroup: "dimensions",
    },
    {
        name: "requires_serial_number",
        labelKey: "fields.requires_serial_number",
        type: "checkbox",
        default: false,
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
        title-key="master_data.items.title"
        subtitle-key="master_data.items.subtitle"
        route-name="admin.items"
        create-label-key="master_data.items.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
        :options="{ itemTypes }"
    />
</template>
