<script setup>
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

defineProps({
    loads: { type: Array, default: () => [] },
});

const severityFor = (status) => ({ green: 'success', yellow: 'warn', red: 'danger' }[status] || 'secondary');
</script>

<template>
    <DataTable :value="loads" stripedRows size="small" responsiveLayout="scroll">
        <Column field="factory_unit" header="Factory Unit" />
        <Column field="available_minutes" header="Available Minutes" />
        <Column field="reserved_minutes" header="Reserved Minutes" />
        <Column field="utilization" header="Utilization %">
            <template #body="{ data }">
                <Tag :value="`${data.utilization}%`" :severity="severityFor(data.status)" />
            </template>
        </Column>
        <Column field="current_queue" header="Current Queue" />
    </DataTable>
</template>
