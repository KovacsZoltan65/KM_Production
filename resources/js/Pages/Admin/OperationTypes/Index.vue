<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    operationTypeCodes: Array,
});

const columns = [
    { field: 'code', header: 'Code' },
    { field: 'name', header: 'Name' },
    { field: 'description', header: 'Description' },
    { field: 'is_active', header: 'Status', type: 'status' },
];

const fields = [
    { name: 'code', label: 'Code', type: 'select', options: 'operationTypeCodes' },
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'description', label: 'Description', type: 'textarea' },
    { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title="Operation Types"
        subtitle="Manage reusable manufacturing operation types."
        route-name="admin.operation-types"
        create-label="Create operation type"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
        :options="{ operationTypeCodes }"
    />
</template>
