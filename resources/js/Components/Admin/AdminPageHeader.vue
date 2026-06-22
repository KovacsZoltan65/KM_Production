<script setup>
import Button from 'primevue/button';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    createLabel: { type: String, default: 'Create' },
    canCreate: { type: Boolean, default: true },
});

defineEmits(['create']);
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-slate-600">{{ subtitle }}</p>
        </div>

        <Button
            v-if="canCreate"
            type="button"
            :label="createLabel"
            icon="pi pi-plus"
            @click="$emit('create')"
        />
    </div>
</template>
