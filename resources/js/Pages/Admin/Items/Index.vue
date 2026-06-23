<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    itemTypes: Array,
});

const columns = [
    { field: 'item_number', header: 'Item number' },
    { field: 'name', header: 'Name' },
    { field: 'item_type', header: 'Type', format: (record) => record.item_type?.replaceAll('_', ' ') },
    { field: 'unit', header: 'Unit' },
    { field: 'requires_serial_number', header: 'Serial', format: (record) => (record.requires_serial_number ? 'Yes' : 'No') },
    { field: 'is_active', header: 'Status', type: 'status' },
];

const fields = [
    { name: 'item_number', label: 'Item number', type: 'text' },
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'item_type', label: 'Type', type: 'select', options: 'itemTypes', default: 'purchased_material' },
    { name: 'unit', label: 'Unit', type: 'text' },
    { name: 'width', label: 'Width', type: 'number' },
    { name: 'length', label: 'Length', type: 'number' },
    { name: 'thickness', label: 'Thickness', type: 'number' },
    { name: 'diameter', label: 'Diameter', type: 'number' },
    { name: 'requires_serial_number', label: 'Requires serial number', type: 'checkbox', default: false },
    { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title="Items"
        subtitle="Manage purchased materials and manufactured items."
        base-url="/admin/items"
        create-label="Create item"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
        :options="{ itemTypes }"
    />
</template>
