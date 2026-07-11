<script setup>
import UnitSelect from "@/Components/Admin/UnitSelect.vue";
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

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
        <label :for="field.name" class="text-sm font-medium">
            {{ label }}<span v-if="field.required" aria-hidden="true"> *</span>
        </label>

        <InputText
            v-if="
                ['text', 'email', 'password', 'date', 'number'].includes(
                    field.type,
                )
            "
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
            class="w-full"
        />
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
        <label
            v-else-if="field.type === 'checkbox'"
            class="flex items-center gap-2 text-sm"
        >
            <Checkbox
                v-model="model"
                :input-id="field.name"
                :invalid="Boolean(error)"
                :disabled="Boolean(field.disabled)"
                :required="Boolean(field.required)"
                binary
            />
            <span>{{ checkboxLabel }}</span>
        </label>

        <p v-if="error" class="text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
