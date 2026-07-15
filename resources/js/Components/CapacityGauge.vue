<script setup>
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string|null} label A(z) label bemeneti értéke.
 * @property {number|null} value A(z) value bemeneti értéke.
 * @property {string|null} detail A(z) detail bemeneti értéke.
 */
/** @type {Props} */
defineProps({
    label: { type: String, required: true },
    value: { type: Number, default: 0 },
    detail: { type: String, default: "" },
});

const toneFor = (value) => {
    if (value >= 90) return "border-red-200 bg-red-50 text-red-700";
    if (value >= 70) return "border-amber-200 bg-amber-50 text-amber-700";
    return "border-emerald-200 bg-emerald-50 text-emerald-700";
};
</script>

<template>
    <div class="rounded border p-4" :class="toneFor(value)">
        <div class="text-sm font-medium">{{ label }}</div>
        <div class="mt-2 text-3xl font-semibold">{{ value }}%</div>
        <div v-if="detail" class="mt-1 text-sm opacity-80">{{ detail }}</div>
    </div>
</template>
