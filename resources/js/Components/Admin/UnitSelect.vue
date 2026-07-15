<script setup>
import { UNIT_OPTIONS } from "@/Constants/units";
import { trans } from "laravel-vue-i18n";
import Select from "primevue/select";
import { computed } from "vue";

defineOptions({
    inheritAttrs: false,
});

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string|null} modelValue A kiválasztott mértékegység.
 * @property {string} id A mező HTML-azonosítója.
 * @property {string} label A mező felirata.
 * @property {string} placeholder A választó helyőrzője.
 * @property {boolean} invalid Jelzi az érvénytelen értéket.
 * @property {boolean} disabled Jelzi a letiltott állapotot.
 * @property {boolean} required Jelzi a kötelező mezőt.
 */
/** @type {Props} */
const props = defineProps({
    modelValue: { type: String, default: null },
    id: { type: String, default: "unit" },
    label: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    invalid: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
});

/**
 * A komponens által kibocsátott események.
 * @typedef {Object} Emits
 * @property {(event: 'update:modelValue', value: string|null) => void} updateModelValue A mértékegység módosítási eseménye.
 */
/** @type {Emits} */
const emit = defineEmits(["update:modelValue"]);

const value = computed({
    get: () => props.modelValue,
    set: (nextValue) => emit("update:modelValue", nextValue),
});

const resolvedPlaceholder = computed(
    () => props.placeholder || trans("fields.unit"),
);
</script>

<template>
    <div v-if="label" class="space-y-2">
        <label :for="id" class="text-sm font-medium">
            {{ label }}
            <span v-if="required" aria-hidden="true">*</span>
        </label>
        <Select
            :id="id"
            v-bind="$attrs"
            v-model="value"
            :options="UNIT_OPTIONS"
            option-label="label"
            option-value="value"
            :placeholder="resolvedPlaceholder"
            :invalid="invalid"
            :disabled="disabled"
            :required="required"
        />
    </div>
    <Select
        v-else
        :id="id"
        v-bind="$attrs"
        v-model="value"
        :options="UNIT_OPTIONS"
        option-label="label"
        option-value="value"
        :placeholder="resolvedPlaceholder"
        :invalid="invalid"
        :disabled="disabled"
        :required="required"
    />
</template>
