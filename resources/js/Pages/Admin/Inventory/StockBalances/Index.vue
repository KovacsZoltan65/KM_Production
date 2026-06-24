<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const number = (value) => Number(value || 0).toFixed(3);

const columns = [
    { field: 'item.item_number', header: 'Item number', sortable: false },
    { field: 'item.name', header: 'Item', sortable: false },
    { field: 'location.code', header: 'Location', sortable: false },
    { field: 'item_batch.batch_number', header: 'Batch', sortable: false, format: (row) => row.item_batch?.batch_number || '-' },
    { field: 'quantity', header: 'Quantity', format: (row) => number(row.quantity) },
    { field: 'reserved_quantity', header: 'Reserved', sortable: false, format: (row) => number(row.reserved_quantity) },
    { field: 'available_quantity', header: 'Available', sortable: false, format: (row) => number(row.available_quantity) },
    { field: 'unit', header: 'Unit', sortable: false },
];
</script>

<template>
    <AdminCrudPage
        title="Stock Balances"
        subtitle="Review current stock quantities, reservations, and availability."
        route-name="admin.inventory.stock-balances"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
    />
</template>
