<script setup>
import Button from 'primevue/button';

defineProps({
    canEdit: { type: Boolean, default: true },
    canDelete: { type: Boolean, default: true },
});

defineEmits(['edit', 'delete']);
</script>

<template>
    <div class="flex justify-end gap-2">
        <Button
            v-if="canEdit"
            type="button"
            icon="pi pi-pencil"
            severity="secondary"
            text
            rounded
            aria-label="Edit"
            @click="$emit('edit')"
        />
        <Button
            v-if="canDelete"
            type="button"
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            aria-label="Delete"
            @click="$emit('delete')"
        />
    </div>
</template>
