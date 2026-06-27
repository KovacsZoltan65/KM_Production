<script setup>
import RiskBadge from '@/Components/RiskBadge.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

defineProps({ risks: { type: Object, required: true } });
</script>

<template>
    <Head title="Production Risk Score" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Production Risk Score</h1>
                <p class="mt-1 text-sm text-slate-600">Risk score for open customer orders.</p>
            </div>
            <DataTable :value="risks.rows" class="rounded border border-slate-200 bg-white">
                <Column field="customer_order" header="Order" sortable />
                <Column field="customer" header="Customer" sortable />
                <Column field="requested_delivery_date" header="Requested Delivery" sortable />
                <Column field="material_shortage" header="Shortage" sortable />
                <Column field="capacity_delay" header="Capacity Delay" sortable />
                <Column field="quality_rejects" header="Quality" sortable />
                <Column field="supplier_delay" header="Supplier Delay" sortable />
                <Column field="risk_score" header="Score" sortable />
                <Column field="risk_level" header="Risk" sortable><template #body="{ data }"><RiskBadge :value="data.risk_level" /></template></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
