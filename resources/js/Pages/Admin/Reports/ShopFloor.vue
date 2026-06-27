<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

defineProps({ report: { type: Object, required: true } });
</script>

<template>
    <Head title="Shop Floor Report" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Shop Floor Report</h1>
                <p class="mt-1 text-sm text-slate-600">Readonly operator task load and cycle time overview.</p>
            </div>
            <DataTable :value="report.rows" data-key="employee" class="rounded border border-slate-200 bg-white">
                <Column field="employee" header="Employee" sortable />
                <Column field="open_tasks" header="Open Tasks" sortable />
                <Column field="in_progress" header="In Progress" sortable />
                <Column field="completed_today" header="Completed Today" sortable />
                <Column field="average_task_time" header="Average Task Time" sortable>
                    <template #body="{ data }">{{ data.average_task_time === null ? '-' : `${data.average_task_time} min` }}</template>
                </Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
