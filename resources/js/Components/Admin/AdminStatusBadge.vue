<script setup>
defineProps({
    active: { type: Boolean, required: true },
});
</script>

<template>
    <span
        class="inline-flex rounded px-2 py-1 text-xs font-medium"
        :class="active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
    >
        {{ active ? 'Active' : 'Inactive' }}
    </span>
</template>
