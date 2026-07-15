<script setup>
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string|null} title A(z) title bemeneti értéke.
 * @property {number|null} value A(z) value bemeneti értéke.
 * @property {string|null} subtitle A(z) subtitle bemeneti értéke.
 */
/** @type {Props} */
defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    subtitle: { type: String, default: "" },
});
</script>

<template>
    <div class="rounded border border-slate-200 bg-white p-4">
        <div class="text-xs font-medium uppercase text-slate-500">
            {{ title }}
        </div>
        <div class="mt-2 text-3xl font-semibold">{{ value }}</div>
        <div v-if="subtitle" class="mt-1 text-sm text-slate-600">
            {{ subtitle }}
        </div>
    </div>
</template>
