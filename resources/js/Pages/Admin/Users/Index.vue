<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: 'name', header: 'Name' },
    { field: 'email', header: 'Email' },
    { field: 'roles', header: 'Roles', sortable: false, format: (record) => record.roles?.join(', ') || '-' },
];

const fields = [
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'email', label: 'Email', type: 'email' },
    { name: 'password', label: 'Password', type: 'password' },
    { name: 'roles', label: 'Roles', type: 'multiselect', options: 'roles', default: [] },
];
</script>

<template>
    <AdminCrudPage
        title="Users"
        subtitle="Manage application users and assigned authorization roles."
        base-url="/admin/users"
        create-label="Create user"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
