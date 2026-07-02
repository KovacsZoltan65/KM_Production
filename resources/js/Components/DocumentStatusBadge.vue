<script setup>
import Tag from 'primevue/tag';
import { trans } from 'laravel-vue-i18n';

defineProps({
    document: { type: Object, required: true },
});
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Tag
            :value="document.is_current ? trans('status.current') : trans('status.archived')"
            :severity="document.is_current ? 'success' : 'secondary'"
        />
        <Tag
            :value="document.approved ? trans('status.approved') : trans('status.pending')"
            :severity="document.approved ? 'info' : 'warn'"
        />
    </div>
</template>
