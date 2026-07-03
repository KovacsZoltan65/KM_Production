<script setup>
import Tag from "primevue/tag";

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
