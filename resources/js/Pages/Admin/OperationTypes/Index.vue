<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    operationTypeCodes: Array,
});

const columns = [
    { field: 'code', headerKey: 'fields.code' },
    { field: 'name', headerKey: 'fields.name' },
    { field: 'description', headerKey: 'fields.description' },
    { field: 'is_active', headerKey: 'fields.status', type: 'status' },
];

const fields = [
    { name: 'code', labelKey: 'fields.code', type: 'select', options: 'operationTypeCodes' },
    { name: 'name', labelKey: 'fields.name', type: 'text' },
    { name: 'description', labelKey: 'fields.description', type: 'textarea' },
    { name: 'is_active', labelKey: 'status.active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.operation_types.title"
        subtitle-key="master_data.operation_types.subtitle"
        route-name="admin.operation-types"
        create-label-key="master_data.operation_types.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
        :options="{ operationTypeCodes }"
    />
</template>
