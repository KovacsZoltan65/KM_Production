<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';

defineProps({ report: { type: Object, required: true } });
const typeLabel = (value) => String(value || '').replaceAll('_', ' ');
</script>

<template>
    <Head title="Production Report" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Production Report</h1>
                <p class="mt-1 text-sm text-slate-600">Readonly production order progress and workshop coverage.</p>
            </div>
            <DataTable :value="report.rows" data-key="id" class="rounded border border-slate-200 bg-white">
                <Column field="production_order" header="Production Order" sortable />
                <Column field="product" header="Product" sortable />
                <Column field="status" header="Status" sortable><template #body="{ data }"><Tag :value="typeLabel(data.status)" class="capitalize" /></template></Column>
                <Column field="factory_unit" header="Factory Unit" />
                <Column field="planned_start" header="Planned Start" sortable />
                <Column field="planned_finish" header="Planned Finish" sortable />
                <Column field="completed_percent" header="Completed Tasks %" sortable>
                    <template #body="{ data }">
                        <div class="min-w-40">
                            <ProgressBar :value="Number(data.completed_percent)" />
                            <div class="mt-1 text-xs text-slate-600">{{ data.completed_tasks }} / {{ data.all_tasks }}</div>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
