<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: 'name', headerKey: 'fields.name' },
    { field: 'permissions', headerKey: 'fields.permissions', sortable: false, format: (record) => record.permissions?.length || 0 },
];

const fields = [
    { name: 'name', labelKey: 'fields.name', type: 'text' },
    { name: 'permissions', labelKey: 'fields.permissions', type: 'multiselect', options: 'permissions', default: [] },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.roles.title"
        subtitle-key="master_data.roles.subtitle"
        route-name="admin.roles"
        create-label-key="master_data.roles.create"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
