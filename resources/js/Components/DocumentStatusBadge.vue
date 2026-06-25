<script setup>
import Tag from 'primevue/tag';

defineProps({
    document: { type: Object, required: true },
});
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Tag
            :value="document.is_current ? 'Current' : 'Archived'"
            :severity="document.is_current ? 'success' : 'secondary'"
        />
        <Tag
            :value="document.approved ? 'Approved' : 'Pending'"
            :severity="document.approved ? 'info' : 'warn'"
        />
    </div>
</template>
