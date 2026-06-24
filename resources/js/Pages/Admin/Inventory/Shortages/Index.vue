<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const number = (value) => Number(value || 0).toFixed(3);

const columns = [
    { field: 'customer_order_item.customer_order.order_number', header: 'Customer order', sortable: false },
    {
        field: 'customer_order_item.production_orders',
        header: 'Production order',
        sortable: false,
        format: (row) => row.customer_order_item?.production_orders?.[0]?.order_number || '-',
    },
    { field: 'required_item.item_number', header: 'Required item number', sortable: false },
    { field: 'required_item.name', header: 'Required item', sortable: false },
    { field: 'missing_quantity', header: 'Missing', format: (row) => number(row.missing_quantity) },
    { field: 'unit', header: 'Unit', sortable: false },
    { field: 'status', header: 'Status' },
];
</script>

<template>
    <AdminCrudPage
        title="Shortages"
        subtitle="Review material requirements with missing quantities."
        base-url="/admin/inventory/shortages"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
    />
</template>
