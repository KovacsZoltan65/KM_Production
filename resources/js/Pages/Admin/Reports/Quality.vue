<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import ProgressBar from 'primevue/progressbar';

defineProps({ report: { type: Object, required: true } });
</script>

<template>
    <Head title="Quality Report" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Quality Report</h1>
                <p class="mt-1 text-sm text-slate-600">Readonly acceptance, rejection, and rework summary by production order.</p>
            </div>
            <DataTable :value="report.rows" data-key="production_order" class="rounded border border-slate-200 bg-white">
                <Column field="production_order" header="Production Order" sortable />
                <Column field="quality_checks" header="Quality Checks" sortable />
                <Column field="accepted" header="Accepted" sortable />
                <Column field="rejected" header="Rejected" sortable />
                <Column field="rework" header="Rework" sortable />
                <Column field="acceptance_rate" header="Acceptance Rate" sortable>
                    <template #body="{ data }"><ProgressBar :value="Number(data.acceptance_rate)" /></template>
                </Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
