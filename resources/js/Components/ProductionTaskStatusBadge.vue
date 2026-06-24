<script setup>
import Tag from 'primevue/tag';

defineProps({ status: String });

const severity = (value) =>
    ({
        planned: 'secondary',
        ready: 'info',
        in_progress: 'warn',
        waiting_for_check: 'contrast',
        completed: 'success',
        rejected: 'danger',
        cancelled: 'danger',
    })[value] || 'secondary';

const label = (value) => String(value || '').replaceAll('_', ' ');
</script>

<template>
    <Tag :value="label(status)" :severity="severity(status)" />
</template>
