<script setup>
import Button from "primevue/button";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string|null} title A(z) title bemeneti értéke.
 * @property {string|null} titleKey A(z) titleKey bemeneti értéke.
 * @property {string|null} subtitle A(z) subtitle bemeneti értéke.
 * @property {string|null} subtitleKey A(z) subtitleKey bemeneti értéke.
 * @property {string|null} createLabel A(z) createLabel bemeneti értéke.
 * @property {string|null} createLabelKey A(z) createLabelKey bemeneti értéke.
 * @property {boolean} canCreate A(z) canCreate bemeneti értéke.
 */
/** @type {Props} */
const props = defineProps({
    title: { type: String, required: true },
    titleKey: { type: String, default: "" },
    subtitle: { type: String, default: "" },
    subtitleKey: { type: String, default: "" },
    createLabel: { type: String, default: "" },
    createLabelKey: { type: String, default: "" },
    canCreate: { type: Boolean, default: true },
});

/**
 * A komponens által kibocsátott események.
 * @typedef {Object} Emits
 * @property {(event: 'create') => void} create A(z) create esemény.
 */
/** @type {Emits} */
defineEmits(["create"]);

const resolvedTitle = computed(() =>
    props.titleKey ? trans(props.titleKey) : props.title,
);
const resolvedSubtitle = computed(() =>
    props.subtitleKey ? trans(props.subtitleKey) : props.subtitle,
);
const resolvedCreateLabel = computed(() =>
    props.createLabelKey
        ? trans(props.createLabelKey)
        : props.createLabel || trans("actions.create"),
);
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-2xl font-semibold">{{ resolvedTitle }}</h1>
            <p v-if="resolvedSubtitle" class="mt-1 text-sm text-slate-600">
                {{ resolvedSubtitle }}
            </p>
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
