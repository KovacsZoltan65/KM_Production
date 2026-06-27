<script setup>
import RiskBadge from '@/Components/RiskBadge.vue';

defineProps({
    recommendation: { type: Object, required: true },
});

const number = (value) => Number(value || 0).toFixed(3);
</script>

<template>
    <div class="rounded border border-slate-200 bg-white p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold">{{ recommendation.item }}</h3>
                <p class="mt-1 text-sm text-slate-600">{{ recommendation.reason }}</p>
            </div>
            <RiskBadge :value="recommendation.risk_level" />
        </div>
        <div class="mt-3 text-2xl font-semibold">
            {{ number(recommendation.recommended_quantity) }} {{ recommendation.unit }}
        </div>
        <div class="mt-2 text-xs text-slate-500">
            Related orders: {{ recommendation.related_customer_orders?.join(', ') || '-' }}
        </div>
    </div>
</template>
