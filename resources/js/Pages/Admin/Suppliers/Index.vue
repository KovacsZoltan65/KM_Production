<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const columns = [
    { field: 'code', header: 'Code' },
    { field: 'name', header: 'Name' },
    { field: 'tax_number', header: 'Tax number' },
    { field: 'email', header: 'Email' },
    { field: 'phone', header: 'Phone' },
    { field: 'is_active', header: 'Status', type: 'status' },
];

const fields = [
    { name: 'code', label: 'Code', type: 'text' },
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'tax_number', label: 'Tax number', type: 'text' },
    { name: 'email', label: 'Email', type: 'email' },
    { name: 'phone', label: 'Phone', type: 'text' },
    { name: 'address', label: 'Address', type: 'textarea' },
    { name: 'notes', label: 'Notes', type: 'textarea' },
    { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title="Suppliers"
        subtitle="Manage supplier master data."
        base-url="/admin/suppliers"
        create-label="Create supplier"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
