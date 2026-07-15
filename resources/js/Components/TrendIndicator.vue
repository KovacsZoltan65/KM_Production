<script setup>
import Tag from "primevue/tag";

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string|null} value A(z) value bemeneti értéke.
 */
/** @type {Props} */
const props = defineProps({
    value: { type: String, default: "stable" },
});

const severity = {
    improving: "success",
    stable: "secondary",
    worsening: "danger",
};
</script>

<template>
    <Tag
        :severity="severity[props.value] || 'secondary'"
        :value="props.value"
        class="capitalize"
    />
</template>
