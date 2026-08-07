<script setup>
import Tag from "primevue/tag";
import { computed } from "vue";

const props = defineProps({ status: { type: String, required: true } });

const severity = computed(
    () =>
        ({
            draft: "secondary",
            proposed: "info",
            approved: "success",
            rejected: "danger",
            cancelled: "contrast",
        })[props.status] || "secondary",
);
</script>

<template>
    <Tag
        :value="$t(`planning.supply_proposals.status.${status}`)"
        :severity="severity"
        :data-status="status"
    />
</template>
