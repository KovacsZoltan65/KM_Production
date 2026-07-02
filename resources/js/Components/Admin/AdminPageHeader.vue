<script setup>
import Button from 'primevue/button';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    titleKey: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    subtitleKey: { type: String, default: '' },
    createLabel: { type: String, default: '' },
    createLabelKey: { type: String, default: '' },
    canCreate: { type: Boolean, default: true },
});

defineEmits(['create']);

const resolvedTitle = computed(() => props.titleKey ? trans(props.titleKey) : props.title);
const resolvedSubtitle = computed(() => props.subtitleKey ? trans(props.subtitleKey) : props.subtitle);
const resolvedCreateLabel = computed(() => props.createLabelKey ? trans(props.createLabelKey) : props.createLabel || trans('actions.create'));
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ resolvedTitle }}</h1>
            <p v-if="resolvedSubtitle" class="mt-1 text-sm text-slate-600">{{ resolvedSubtitle }}</p>
        </div>

        <Button
            v-if="canCreate"
            type="button"
            :label="resolvedCreateLabel"
            icon="pi pi-plus"
            @click="$emit('create')"
        />
    </div>
</template>
