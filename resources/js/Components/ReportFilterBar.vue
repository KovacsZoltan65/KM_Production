<script setup>
import Button from 'primevue/button';

defineEmits(['apply', 'reset']);
</script>

<template>
    <section class="rounded border border-slate-200 bg-white p-3">
        <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <slot />
            </div>
            <div class="flex gap-2">
                <Button type="button" :label="$t('actions.apply')" icon="pi pi-filter" @click="$emit('apply')" />
                <Button type="button" :label="$t('actions.reset')" icon="pi pi-refresh" severity="secondary" outlined @click="$emit('reset')" />
            </div>
        </div>
    </section>
</template>
