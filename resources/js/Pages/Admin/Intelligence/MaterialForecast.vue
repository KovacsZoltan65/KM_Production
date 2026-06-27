<script setup>
import MaterialRiskTable from '@/Components/MaterialRiskTable.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ forecast: { type: Object, required: true } });
</script>

<template>
    <Head title="Material Consumption Forecast" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Material Consumption Forecast</h1>
                <p class="mt-1 text-sm text-slate-600">Stockout estimate from production consumption movements.</p>
            </div>
            <MaterialRiskTable :rows="forecast.rows" />
        </div>
    </AdminLayout>
</template>
