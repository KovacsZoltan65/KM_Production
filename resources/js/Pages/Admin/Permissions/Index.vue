<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const columns = [
    { field: 'name', headerKey: 'fields.name' },
    { field: 'guard_name', headerKey: 'fields.guard' },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.permissions.title"
        subtitle-key="master_data.permissions.subtitle"
        route-name="admin.permissions"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
    />
</template>
