<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const columns = [
    { field: 'code', headerKey: 'fields.code' },
    { field: 'name', headerKey: 'fields.name' },
    { field: 'is_active', headerKey: 'fields.status', type: 'status' },
];

const fields = [
    { name: 'code', labelKey: 'fields.code', type: 'text' },
    { name: 'name', labelKey: 'fields.name', type: 'text' },
    { name: 'description', labelKey: 'fields.description', type: 'textarea' },
    { name: 'is_active', labelKey: 'status.active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.professional_roles.title"
        subtitle-key="master_data.professional_roles.subtitle"
        route-name="admin.professional-roles"
        create-label-key="master_data.professional_roles.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
