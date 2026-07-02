<script setup>
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    status: { type: String, required: true },
});

const tones = {
    draft: 'bg-slate-100 text-slate-700',
    calculated: 'bg-cyan-50 text-cyan-700',
    approved: 'bg-blue-50 text-blue-700',
    in_progress: 'bg-violet-50 text-violet-700',
    completed: 'bg-emerald-50 text-emerald-700',
    cancelled: 'bg-red-50 text-red-700',
    planned: 'bg-indigo-50 text-indigo-700',
    released: 'bg-teal-50 text-teal-700',
};
</script>

<template>
    <span class="inline-flex rounded px-2 py-1 text-xs font-medium" :class="tones[props.status] || tones.draft">
        {{ trans(`status.${props.status}`) }}
    </span>
</template>
