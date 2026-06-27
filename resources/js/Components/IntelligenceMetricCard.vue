<script setup>
defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded border border-slate-200 bg-white p-4">
        <div class="text-xs font-medium uppercase text-slate-500">{{ title }}</div>
        <div class="mt-2 text-3xl font-semibold">{{ value }}</div>
        <div v-if="subtitle" class="mt-1 text-sm text-slate-600">{{ subtitle }}</div>
    </div>
</template>
