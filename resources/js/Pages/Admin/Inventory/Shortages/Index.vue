<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
});

const number = (value) => Number(value || 0).toFixed(3);

const columns = [
    { field: 'customer_order_item.customer_order.order_number', headerKey: 'fields.customer_order', sortable: false },
    {
        field: 'customer_order_item.production_orders',
        headerKey: 'fields.production_order',
        sortable: false,
        format: (row) => row.customer_order_item?.production_orders?.[0]?.order_number || '-',
    },
    { field: 'required_item.item_number', headerKey: 'fields.required_item_number', sortable: false },
    { field: 'required_item.name', headerKey: 'fields.required_item', sortable: false },
    { field: 'missing_quantity', headerKey: 'fields.missing', format: (row) => number(row.missing_quantity) },
    { field: 'unit', headerKey: 'fields.unit', sortable: false },
    { field: 'status', headerKey: 'fields.status' },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="inventory.shortages.title"
        subtitle-key="inventory.shortages.subtitle"
        route-name="admin.inventory.shortages"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
    />
</template>
