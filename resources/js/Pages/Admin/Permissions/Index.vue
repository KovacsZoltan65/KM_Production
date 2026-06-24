<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const columns = [
    { field: 'name', header: 'Name' },
    { field: 'guard_name', header: 'Guard' },
];
</script>

<template>
    <AdminCrudPage
        title="Permissions"
        subtitle="Read-only list of system permissions."
        route-name="admin.permissions"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
    />
</template>
