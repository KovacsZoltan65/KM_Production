<script setup>
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string|null} title A(z) title bemeneti értéke.
 */
/** @type {Props} */
defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <section class="rounded border border-slate-200 bg-white p-4">
        <h2 class="font-semibold">{{ title }}</h2>
        <div class="mt-4">
            <slot />
        </div>
    </section>
</template>
