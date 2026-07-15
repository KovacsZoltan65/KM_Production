<script setup>
import Button from "primevue/button";
import { trans } from "laravel-vue-i18n";

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {boolean} canEdit A(z) canEdit bemeneti értéke.
 * @property {boolean} canDelete A(z) canDelete bemeneti értéke.
 */
/** @type {Props} */
defineProps({
    canEdit: { type: Boolean, default: true },
    canDelete: { type: Boolean, default: true },
});

/**
 * A komponens által kibocsátott események.
 * @typedef {Object} Emits
 * @property {(event: 'edit') => void} edit A(z) edit esemény.
 * @property {(event: 'delete') => void} delete A(z) delete esemény.
 */
/** @type {Emits} */
defineEmits(["edit", "delete"]);
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
            :aria-label="trans('actions.edit')"
            @click="$emit('edit')"
        />
        <Button
            v-if="canDelete"
            type="button"
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            :aria-label="trans('actions.delete')"
            @click="$emit('delete')"
        />
    </div>
</template>
