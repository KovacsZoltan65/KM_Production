<script setup>
import Tag from 'primevue/tag';

const props = defineProps({
    value: { type: String, default: 'normal' },
});

const severity = {
    high: 'danger',
    critical: 'danger',
    bottleneck: 'danger',
    medium: 'warn',
    warning: 'warn',
    watch: 'warn',
    low: 'success',
    normal: 'success',
    unknown: 'secondary',
};

const label = (value) => String(value || 'normal').replaceAll('_', ' ');
</script>

<template>
    <Tag :severity="severity[props.value] || 'secondary'" :value="label(props.value)" class="capitalize" />
</template>
