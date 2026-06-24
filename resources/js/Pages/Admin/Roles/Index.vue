<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: 'name', header: 'Name' },
    { field: 'permissions', header: 'Permissions', sortable: false, format: (record) => record.permissions?.length || 0 },
];

const fields = [
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'permissions', label: 'Permissions', type: 'multiselect', options: 'permissions', default: [] },
];
</script>

<template>
    <AdminCrudPage
        title="Roles"
        subtitle="Manage authorization roles and their permissions."
        route-name="admin.roles"
        create-label="Create role"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
