<script setup>
import Select from "primevue/select";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

const props = defineProps({
    modelValue: { type: String, default: "hu" },
    options: {
        type: Array,
        default: () => [{ value: "hu" }, { value: "en" }],
    },
    placeholder: { type: String, default: "" },
});

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
