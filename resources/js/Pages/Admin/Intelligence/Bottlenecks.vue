<script setup>
import BottleneckTable from '@/Components/BottleneckTable.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ analysis: { type: Object, required: true } });
</script>

<template>
    <Head title="Bottleneck Analysis" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Bottleneck Analysis</h1>
                <p class="mt-1 text-sm text-slate-600">Factory unit utilization, queues, and late order pressure.</p>
            </div>
            <BottleneckTable :rows="analysis.rows" />
        </div>
    </AdminLayout>
</template>
