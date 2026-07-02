<script setup>
import Select from "primevue/select";
import { computed } from "vue";

const props = defineProps({
    modelValue: { type: String, default: "hu" },
    options: {
        type: Array,
        default: () => [
            { label: "Magyar", value: "hu" },
            { label: "English", value: "en" },
        ],
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
</script>

<template>
    <Select
        v-model="selectedLocale"
        :options="options"
        option-label="label"
        option-value="value"
        :placeholder="placeholder"
        class="w-full"
    />
</template>
