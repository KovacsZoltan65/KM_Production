<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmployeeLoadTable from '@/Components/EmployeeLoadTable.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    loads: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="space-y-5">
        <div>
            <h1 class="text-2xl font-semibold">Employee Capacity</h1>
            <p class="text-sm text-slate-500">Working minutes, reserved minutes and assigned tasks.</p>
        </div>
        <EmployeeLoadTable :loads="loads" />
    </section>
</template>
