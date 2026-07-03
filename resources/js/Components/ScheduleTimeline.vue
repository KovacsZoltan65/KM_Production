<script setup>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";

defineProps({
    rows: { type: Array, default: () => [] },
});
</script>

<template>
    <DataTable :value="rows" stripedRows size="small" responsiveLayout="scroll">
        <Column field="factory_unit" header="Factory Unit" />
        <Column field="production_task" header="Production Task" />
        <Column field="start" header="Start" />
        <Column field="finish" header="Finish" />
        <Column field="duration" header="Duration">
            <template #body="{ data }">{{ data.duration }} min</template>
        </Column>
        <Column field="employee" header="Employee" />
        <Column field="status" header="Status">
            <template #body="{ data }"
                ><Tag :value="data.status" severity="info"
            /></template>
        </Column>
    </DataTable>
</template>
