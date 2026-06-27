<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';

defineProps({ report: { type: Object, required: true } });
</script>

<template>
    <Head title="Inventory Report" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Inventory Report</h1>
                <p class="mt-1 text-sm text-slate-600">Readonly current, reserved, and available stock by item and location.</p>
            </div>
            <DataTable :value="report.rows" data-key="item" class="rounded border border-slate-200 bg-white">
                <Column field="item" header="Item" sortable />
                <Column field="location" header="Location" sortable />
                <Column field="current_stock" header="Current Stock" sortable />
                <Column field="reserved" header="Reserved" sortable />
                <Column field="available" header="Available" sortable>
                    <template #body="{ data }">
                        <Tag v-if="data.is_shortage" :value="data.available" severity="danger" />
                        <span v-else>{{ data.available }}</span>
                    </template>
                </Column>
                <Column field="batch_count" header="Batch Count" sortable />
            </DataTable>
        </div>
    </AdminLayout>
</template>
