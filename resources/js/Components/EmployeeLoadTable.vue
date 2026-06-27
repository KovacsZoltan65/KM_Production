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
        <Column field="employee" header="Employee" />
        <Column field="professional_role" header="Professional Role" />
        <Column field="working_minutes" header="Working Minutes" />
        <Column field="reserved_minutes" header="Reserved Minutes" />
        <Column field="utilization" header="Utilization">
            <template #body="{ data }">
                <Tag :value="`${data.utilization}%`" :severity="severityFor(data.status)" />
            </template>
        </Column>
        <Column field="assigned_tasks" header="Assigned Tasks" />
    </DataTable>
</template>
