<script setup>
import Select from "primevue/select";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

/**
 * Választható listaelem.
 * @typedef {Object} SelectOption
 * @property {string} value A nyelv ISO-kódja.
 * @property {string} [label] A nyelv megjelenített neve.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string} modelValue Az aktív nyelvkód.
 * @property {SelectOption[]} options A választható nyelvek.
 * @property {string} placeholder A választó helyőrzője.
 */
/** @type {Props} */
const props = defineProps({
    modelValue: { type: String, default: "hu" },
    options: {
        type: Array,
        default: () => [{ value: "hu" }, { value: "en" }],
    },
    placeholder: { type: String, default: "" },
});

/**
 * A komponens által kibocsátott események.
 * @typedef {Object} Emits
 * @property {(event: 'update:modelValue', value: string) => void} updateModelValue A nyelvkód módosítási eseménye.
 * @property {(event: 'change', value: string) => void} change A nyelvváltási esemény.
 */
/** @type {Emits} */
const emit = defineEmits(["update:modelValue", "change"]);

const selectedLocale = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
        emit("change", value);
    },
});

const resolvedOptions = computed(() =>
    props.options.map((option) => ({
        ...option,
        label: option.label || trans(`common.locales.${option.value}`),
    })),
);
</script>

<template>
    <Select
        v-model="selectedLocale"
        :options="resolvedOptions"
        option-label="label"
        option-value="value"
        :placeholder="placeholder"
        class="w-full"
    />
</template>
