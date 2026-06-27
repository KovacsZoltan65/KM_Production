<script setup>
import TrendIndicator from '@/Components/TrendIndicator.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

defineProps({ trends: { type: Object, required: true } });
</script>

<template>
    <Head title="Quality Trends" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Quality Trends</h1>
                <p class="mt-1 text-sm text-slate-600">Accepted, rework, rejected, and defect trend by order.</p>
            </div>
            <DataTable :value="trends.rows" class="rounded border border-slate-200 bg-white">
                <Column field="item" header="Item" sortable />
                <Column field="production_order" header="Production Order" sortable />
                <Column field="accepted_count" header="Accepted" sortable />
                <Column field="rework_count" header="Rework" sortable />
                <Column field="rejected_count" header="Rejected" sortable />
                <Column field="defect_rate" header="Defect %" sortable />
                <Column field="trend" header="Trend" sortable><template #body="{ data }"><TrendIndicator :value="data.trend" /></template></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
