<script setup>
import RecommendationCard from '@/Components/RecommendationCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ recommendations: { type: Object, required: true } });
</script>

<template>
    <Head title="Procurement Recommendations" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Procurement Recommendations</h1>
                <p class="mt-1 text-sm text-slate-600">Readonly purchase suggestions from missing requirements and open purchase coverage.</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <RecommendationCard v-for="item in recommendations.rows" :key="item.item_id" :recommendation="item" />
            </div>
            <div v-if="recommendations.rows.length === 0" class="rounded border border-slate-200 bg-white p-6 text-sm text-slate-600">
                No procurement recommendations right now.
            </div>
        </div>
    </AdminLayout>
</template>
