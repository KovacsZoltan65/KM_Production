<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: 'code', headerKey: 'fields.code' },
    { field: 'name', headerKey: 'fields.name' },
    { field: 'location_type', headerKey: 'fields.type' },
    { field: 'factoryUnit.name', headerKey: 'fields.factory_unit', sortable: false, format: (record) => record.factory_unit?.name || '-' },
    { field: 'is_active', headerKey: 'fields.status', type: 'status' },
];

const fields = [
    { name: 'factory_unit_id', labelKey: 'fields.factory_unit', type: 'select', options: 'factoryUnits', optionLabel: 'name', optionValue: 'id' },
    { name: 'code', labelKey: 'fields.code', type: 'text' },
    { name: 'name', labelKey: 'fields.name', type: 'text' },
    { name: 'location_type', labelKey: 'fields.location_type', type: 'select', options: 'locationTypes', enumKey: 'enum.location_type' },
    { name: 'description', labelKey: 'fields.description', type: 'textarea' },
    { name: 'is_active', labelKey: 'status.active', type: 'checkbox', default: true },
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
