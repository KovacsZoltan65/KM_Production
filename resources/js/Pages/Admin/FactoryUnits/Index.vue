<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const columns = [
    { field: 'code', headerKey: 'fields.code' },
    { field: 'name', headerKey: 'fields.name' },
    { field: 'daily_capacity_minutes', headerKey: 'fields.daily_capacity' },
    { field: 'shift_count', headerKey: 'fields.shifts' },
    { field: 'is_active', headerKey: 'fields.status', type: 'status' },
];

const fields = [
    { name: 'code', labelKey: 'fields.code', type: 'text' },
    { name: 'name', labelKey: 'fields.name', type: 'text' },
    { name: 'description', labelKey: 'fields.description', type: 'textarea' },
    { name: 'daily_capacity_minutes', labelKey: 'fields.daily_capacity_minutes', type: 'number' },
    { name: 'shift_count', labelKey: 'fields.shift_count', type: 'number', default: 1 },
    { name: 'is_active', labelKey: 'status.active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.factory_units.title"
        subtitle-key="master_data.factory_units.subtitle"
        route-name="admin.factory-units"
        create-label-key="master_data.factory_units.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
