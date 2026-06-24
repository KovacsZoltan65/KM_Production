<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const columns = [
    { field: 'code', header: 'Code' },
    { field: 'name', header: 'Name' },
    { field: 'is_active', header: 'Status', type: 'status' },
];

const fields = [
    { name: 'code', label: 'Code', type: 'text' },
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'description', label: 'Description', type: 'textarea' },
    { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title="Professional Roles"
        subtitle="Manage professional employee roles separately from authorization roles."
        route-name="admin.professional-roles"
        create-label="Create professional role"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
