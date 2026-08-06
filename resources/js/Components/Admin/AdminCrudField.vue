<script setup>
import UnitSelect from "@/Components/Admin/UnitSelect.vue";
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import MultiSelect from "primevue/multiselect";
import DatePicker from "primevue/datepicker";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import Password from "primevue/password";
import IftaLabel from "primevue/iftalabel";

import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";

import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

/**
 * Választható listaelem.
 * @typedef {Object} SelectOption
 * @property {number|string} [id] Az elem azonosítója.
 * @property {number|string|boolean} [value] Az elem értéke.
 * @property {string} [label] Az elem felirata.
 * @property {string} [name] Az elem neve.
 * @property {string} [code] Az elem kódja.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ checkboxLabel?: string, checkboxLabelKey?: string, disabled?: boolean, enumKey?: string, label?: string, labelKey?: string, max?: number, min?: number, name: string, optionLabel?: string, optionValue?: string, options?: string|SelectOption[], placeholder?: string, required?: boolean, rows?: number, step?: number, type?: string }} field A szerkesztendő mező konfigurációja.
 * @property {string|null} error A(z) error bemeneti értéke.
 * @property {Object.<string, SelectOption[]>} options A(z) options bemeneti értéke.
 */
/** @type {Props} */
const props = defineProps({
    field: { type: Object, required: true },
    error: { type: String, default: "" },
    options: { type: Object, default: () => ({}) },
});

const model = defineModel({ default: null });

const label = computed(() =>
    props.field.labelKey ? trans(props.field.labelKey) : props.field.label,
);

const checkboxLabel = computed(() =>
    props.field.checkboxLabelKey
        ? trans(props.field.checkboxLabelKey)
        : props.field.checkboxLabel || label.value,
);

const iconClass = computed(() =>
    props.field.icon ? `pi pi-${props.field.icon}` : null,
);

const optionItems = computed(() => {
    const source =
        props.options[props.field.options] || props.field.options || [];

    return source.map((option) => {
        if (typeof option === "string" || typeof option === "number") {
            return { label: option, value: option };
        }

        return {
            label:
                props.field.enumKey && (option.value ?? option.id)
                    ? trans(
                          `${props.field.enumKey}.${option.value ?? option.id}`,
                      )
                    : props.field.optionLabel
                      ? option[props.field.optionLabel]
                      : option.label || option.name || option.code,
            value: props.field.optionValue
                ? option[props.field.optionValue]
                : (option.value ?? option.id),
        };
    });
});
</script>

<template>
    <div class="min-w-0 space-y-2">
        <IftaLabel :for="field.name" class="text-sm font-medium">
            {{ label }}
            <span
                v-if="field.required"
                class="ml-0.5 text-red-500"
                aria-hidden="true"
            >
                *
            </span>
        </IftaLabel>

        <div
            v-if="['text', 'email', 'number'].includes(field.type)"
            class="flex items-start gap-2"
            data-test="field-control-row"
        >
            <IconField v-if="field.icon" class="min-w-0 flex-1">
                <InputIcon :class="iconClass" />

                <InputText
                    :id="field.name"
                    v-model="model"
                    :type="field.type"
                    :invalid="Boolean(error)"
                    :disabled="Boolean(field.disabled)"
                    :placeholder="field.placeholder"
                    :required="Boolean(field.required)"
                    :min="field.min"
                    :max="field.max"
                    :step="field.step"
                    :aria-required="field.required"
                    class="w-full"
                />
            </IconField>

            <InputText
                v-else
                :id="field.name"
                v-model="model"
                :type="field.type"
                :invalid="Boolean(error)"
                :disabled="Boolean(field.disabled)"
                :placeholder="field.placeholder"
                :required="Boolean(field.required)"
                :min="field.min"
                :max="field.max"
                :step="field.step"
                :aria-required="field.required"
                class="min-w-0 flex-1"
            />

            <slot name="action" />
        </div>

        <!-- PASSWORD -->
        <div
            v-else-if="field.type === 'password'"
            class="flex items-start gap-2"
            data-test="field-control-row"
        >
            <Password
                :input-id="field.name"
                v-model="model"
                :invalid="Boolean(error)"
                :disabled="Boolean(field.disabled)"
                :placeholder="field.placeholder"
                :required="Boolean(field.required)"
                :aria-required="field.required"
                :feedback="false"
                toggle-mask
                fluid
                class="min-w-0 flex-1"
                input-class="w-full"
            />

            <slot name="action" />
        </div>

        <div
            v-else-if="field.type === 'date'"
            class="flex items-start gap-2"
            data-test="field-control-row"
        >
            <DatePicker
                :input-id="field.name"
                v-model="model"
                update-model-type="string"
                date-format="yy-mm-dd"
                show-icon
                icon-display="input"
                :show-button-bar="field.showButtonBar !== false"
                :manual-input="field.manualInput !== false"
                :invalid="Boolean(error)"
                :disabled="Boolean(field.disabled)"
                :placeholder="field.placeholder"
                :required="Boolean(field.required)"
                :aria-required="field.required"
                fluid
                class="min-w-0 flex-1"
            />

            <slot name="action" />
        </div>

        <Textarea
            v-else-if="field.type === 'textarea'"
            :id="field.name"
            v-model="model"
            :rows="field.rows || 3"
            :invalid="Boolean(error)"
            :disabled="Boolean(field.disabled)"
            :placeholder="field.placeholder"
            :required="Boolean(field.required)"
            class="w-full"
        />

        <UnitSelect
            v-else-if="field.type === 'unit'"
            :id="field.name"
            v-model="model"
            :invalid="Boolean(error)"
            :disabled="Boolean(field.disabled)"
            :placeholder="field.placeholder"
            :required="Boolean(field.required)"
            class="w-full"
        />

        <Select
            v-else-if="field.type === 'select'"
            :id="field.name"
            v-model="model"
            :options="optionItems"
            option-label="label"
            option-value="value"
            :invalid="Boolean(error)"
            :disabled="Boolean(field.disabled)"
            :placeholder="field.placeholder"
            :required="Boolean(field.required)"
            show-clear
            class="w-full"
        />

        <MultiSelect
            v-else-if="field.type === 'multiselect'"
            :id="field.name"
            v-model="model"
            :options="optionItems"
            option-label="label"
            option-value="value"
            :invalid="Boolean(error)"
            :disabled="Boolean(field.disabled)"
            :placeholder="field.placeholder"
            :required="Boolean(field.required)"
            display="chip"
            class="w-full"
        />

        <IftaLabel
            v-else-if="field.type === 'checkbox'"
            :for="field.name"
            class="inline-flex cursor-pointer items-center gap-3"
        >
            <Checkbox
                v-model="model"
                :name="field.name"
                :input-id="field.name"
                :invalid="Boolean(error)"
                :disabled="Boolean(field.disabled)"
                :required="Boolean(field.required)"
                :aria-required="field.required"
                binary
            />

            <span class="select-none"
                >&nbsp;
                {{ checkboxLabel }}
            </span>
        </IftaLabel>

        <p v-if="error" class="text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
