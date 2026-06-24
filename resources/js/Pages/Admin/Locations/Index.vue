<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: 'code', header: 'Code' },
    { field: 'name', header: 'Name' },
    { field: 'location_type', header: 'Type' },
    { field: 'factoryUnit.name', header: 'Factory unit', sortable: false, format: (record) => record.factory_unit?.name || '-' },
    { field: 'is_active', header: 'Status', type: 'status' },
];

const fields = [
    { name: 'factory_unit_id', label: 'Factory unit', type: 'select', options: 'factoryUnits', optionLabel: 'name', optionValue: 'id' },
    { name: 'code', label: 'Code', type: 'text' },
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'location_type', label: 'Location type', type: 'select', options: 'locationTypes' },
    { name: 'description', label: 'Description', type: 'textarea' },
    { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title="Locations"
        subtitle="Manage warehouses, workshops and quality areas."
        route-name="admin.locations"
        create-label="Create location"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
