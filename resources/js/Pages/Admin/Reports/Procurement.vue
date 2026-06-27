<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

defineProps({ report: { type: Object, required: true } });
</script>

<template>
    <Head title="Procurement Report" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Procurement Report</h1>
                <p class="mt-1 text-sm text-slate-600">Readonly supplier purchase order and receipt status summary.</p>
            </div>
            <DataTable :value="report.rows" data-key="supplier" class="rounded border border-slate-200 bg-white">
                <Column field="supplier" header="Supplier" sortable />
                <Column field="purchase_orders" header="Purchase Orders" sortable />
                <Column field="open" header="Open" sortable />
                <Column field="closed" header="Closed" sortable />
                <Column field="goods_receipts_pending" header="Goods Receipts Pending" sortable />
            </DataTable>
        </div>
    </AdminLayout>
</template>
