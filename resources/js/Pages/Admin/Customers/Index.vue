<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const columns = [
    { field: 'code', headerKey: 'fields.code' },
    { field: 'name', headerKey: 'fields.name' },
    { field: 'tax_number', headerKey: 'fields.tax_number' },
    { field: 'email', headerKey: 'fields.email' },
    { field: 'phone', headerKey: 'fields.phone' },
    { field: 'is_active', headerKey: 'fields.status', type: 'status' },
];

const fields = [
    { name: 'code', labelKey: 'fields.code', type: 'text' },
    { name: 'name', labelKey: 'fields.name', type: 'text' },
    { name: 'tax_number', labelKey: 'fields.tax_number', type: 'text' },
    { name: 'email', labelKey: 'fields.email', type: 'email' },
    { name: 'phone', labelKey: 'fields.phone', type: 'text' },
    { name: 'billing_address', labelKey: 'fields.billing_address', type: 'textarea' },
    { name: 'shipping_address', labelKey: 'fields.shipping_address', type: 'textarea' },
    { name: 'notes', labelKey: 'fields.notes', type: 'textarea' },
    { name: 'is_active', labelKey: 'status.active', type: 'checkbox', default: true },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.customers.title"
        subtitle-key="master_data.customers.subtitle"
        route-name="admin.customers"
        create-label-key="master_data.customers.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    />
</template>
