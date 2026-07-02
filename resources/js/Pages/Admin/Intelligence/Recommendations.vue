<script setup>
import RecommendationCard from "@/Components/RecommendationCard.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

defineProps({ recommendations: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.recommendations.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.recommendations.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.recommendations.subtitle") }}
                </p>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <RecommendationCard
                    v-for="item in recommendations.rows"
                    :key="item.item_id"
                    :recommendation="item"
                />
            </div>
            <div
                v-if="recommendations.rows.length === 0"
                class="rounded border border-slate-200 bg-white p-6 text-sm text-slate-600"
            >
                {{ $t("intelligence.recommendations.empty") }}
            </div>
        </div>
    </AdminLayout>
</template>
