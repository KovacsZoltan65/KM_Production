<script setup>
import Tag from "primevue/tag";
import { trans } from "laravel-vue-i18n";

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string|null} status A(z) status bemeneti értéke.
 */
/** @type {Props} */
defineProps({ status: String });

const severity = (value) =>
    ({
        planned: "secondary",
        ready: "info",
        in_progress: "warn",
        waiting_for_check: "contrast",
        completed: "success",
        rejected: "danger",
        cancelled: "danger",
    })[value] || "secondary";

const label = (value) => trans(`status.${value}`);
</script>

<template>
    <Tag :value="label(status)" :severity="severity(status)" />
</template>
