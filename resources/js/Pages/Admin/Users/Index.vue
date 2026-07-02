<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: 'name', headerKey: 'fields.name' },
    { field: 'email', headerKey: 'fields.email' },
    { field: 'roles', headerKey: 'fields.roles', sortable: false, format: (record) => record.roles?.join(', ') || '-' },
];

const fields = [
    { name: 'name', labelKey: 'fields.name', type: 'text' },
    { name: 'email', labelKey: 'fields.email', type: 'email' },
    { name: 'password', labelKey: 'fields.password', type: 'password' },
    { name: 'roles', labelKey: 'fields.roles', type: 'multiselect', options: 'roles', default: [] },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.users.title"
        subtitle-key="master_data.users.subtitle"
        route-name="admin.users"
        create-label-key="master_data.users.create"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
