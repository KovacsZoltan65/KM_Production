<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const columns = [
    { field: 'code', header: 'Code' },
    { field: 'name', header: 'Name' },
    { field: 'daily_capacity_minutes', header: 'Daily capacity' },
    { field: 'shift_count', header: 'Shifts' },
    { field: 'is_active', header: 'Status', type: 'status' },
];

const fields = [
    { name: 'code', label: 'Code', type: 'text' },
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'description', label: 'Description', type: 'textarea' },
    { name: 'daily_capacity_minutes', label: 'Daily capacity minutes', type: 'number' },
    { name: 'shift_count', label: 'Shift count', type: 'number', default: 1 },
    { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title="Factory Units"
        subtitle="Manage manufacturing units."
        route-name="admin.factory-units"
        create-label="Create factory unit"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
