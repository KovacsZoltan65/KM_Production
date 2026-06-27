<script setup>
import RiskBadge from '@/Components/RiskBadge.vue';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

defineProps({
    rows: { type: Array, default: () => [] },
});

const number = (value) => (value === null || value === undefined ? '-' : Number(value).toFixed(3));
</script>

<template>
    <DataTable :value="rows" class="rounded border border-slate-200 bg-white">
        <Column field="item" header="Item" sortable />
        <Column field="current_stock" header="Current" sortable><template #body="{ data }">{{ number(data.current_stock) }}</template></Column>
        <Column field="reserved_quantity" header="Reserved" sortable><template #body="{ data }">{{ number(data.reserved_quantity) }}</template></Column>
        <Column field="available_quantity" header="Available" sortable><template #body="{ data }">{{ number(data.available_quantity) }}</template></Column>
        <Column field="average_daily_consumption" header="Avg Daily Use" sortable><template #body="{ data }">{{ number(data.average_daily_consumption) }}</template></Column>
        <Column field="days_until_stockout" header="Days Left" sortable><template #body="{ data }">{{ data.days_until_stockout ?? '-' }}</template></Column>
        <Column field="risk_level" header="Risk" sortable>
            <template #body="{ data }"><RiskBadge :value="data.risk_level" /></template>
        </Column>
    </DataTable>
</template>
