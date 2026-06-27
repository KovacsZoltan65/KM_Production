<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

defineProps({ performance: { type: Object, required: true } });
</script>

<template>
    <Head title="Supplier Performance" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Supplier Performance</h1>
                <p class="mt-1 text-sm text-slate-600">Purchase order and goods receipt delivery timing.</p>
            </div>
            <DataTable :value="performance.rows" class="rounded border border-slate-200 bg-white">
                <Column field="supplier" header="Supplier" sortable />
                <Column field="purchase_orders_count" header="Purchase Orders" sortable />
                <Column field="goods_receipts_count" header="Receipts" sortable />
                <Column field="average_delivery_days" header="Avg Delivery Days" sortable />
                <Column field="late_delivery_count" header="Late Deliveries" sortable />
                <Column field="on_time_rate" header="On Time %" sortable />
            </DataTable>
        </div>
    </AdminLayout>
</template>
