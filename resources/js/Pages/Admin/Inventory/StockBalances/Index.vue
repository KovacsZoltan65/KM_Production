<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const number = (value) => Number(value || 0).toFixed(3);

const columns = [
    { field: 'item.item_number', headerKey: 'fields.item_number', sortable: false },
    { field: 'item.name', headerKey: 'fields.item', sortable: false },
    { field: 'location.code', headerKey: 'fields.location', sortable: false },
    { field: 'item_batch.batch_number', headerKey: 'fields.batch', sortable: false, format: (row) => row.item_batch?.batch_number || '-' },
    { field: 'quantity', headerKey: 'fields.quantity', format: (row) => number(row.quantity) },
    { field: 'reserved_quantity', headerKey: 'fields.reserved', sortable: false, format: (row) => number(row.reserved_quantity) },
    { field: 'available_quantity', headerKey: 'fields.available', sortable: false, format: (row) => number(row.available_quantity) },
    { field: 'unit', headerKey: 'fields.unit', sortable: false },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="inventory.stock_balances.title"
        subtitle-key="inventory.stock_balances.subtitle"
        route-name="admin.inventory.stock-balances"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
    />
</template>
