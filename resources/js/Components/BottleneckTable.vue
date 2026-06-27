<script setup>
import RiskBadge from '@/Components/RiskBadge.vue';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import ProgressBar from 'primevue/progressbar';

defineProps({
    rows: { type: Array, default: () => [] },
});
</script>

<template>
    <DataTable :value="rows" class="rounded border border-slate-200 bg-white">
        <Column field="factory_unit" header="Factory Unit" sortable />
        <Column field="reserved_minutes" header="Reserved" sortable />
        <Column field="available_minutes" header="Available" sortable />
        <Column field="utilization_percent" header="Utilization" sortable>
            <template #body="{ data }">
                <div class="min-w-40">
                    <ProgressBar :value="Number(data.utilization_percent)" />
                    <div class="mt-1 text-xs text-slate-600">{{ data.utilization_percent }}%</div>
                </div>
            </template>
        </Column>
        <Column field="queue_length" header="Queue" sortable />
        <Column field="average_task_duration" header="Avg Task Min" sortable />
        <Column field="late_related_orders" header="Late Orders" sortable />
        <Column field="status" header="Status" sortable>
            <template #body="{ data }"><RiskBadge :value="data.status" /></template>
        </Column>
    </DataTable>
</template>
