<script setup>
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    operationTypeOptions: { type: Array, default: () => [] },
    factoryUnitOptions: { type: Array, default: () => [] },
    professionalRoleOptions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const rows = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const labelOptions = (items) => items.map((item) => ({ ...item, label: `${item.code} - ${item.name}` }));
const operationTypeChoices = computed(() => labelOptions(props.operationTypeOptions));
const factoryUnitChoices = computed(() => labelOptions(props.factoryUnitOptions));
const professionalRoleChoices = computed(() => labelOptions(props.professionalRoleOptions));

const addRow = () => {
    rows.value = [
        ...rows.value,
        {
            step_order: rows.value.length + 1,
            operation_type_id: null,
            factory_unit_id: null,
            professional_role_id: null,
            estimated_duration_minutes: 30,
            requires_quality_check: false,
            instructions: '',
        },
    ];
};

const updateRow = (index, key, value) => {
    rows.value = rows.value.map((row, rowIndex) => (rowIndex === index ? { ...row, [key]: value } : row));
};

const removeRow = (index) => {
    rows.value = rows.value.filter((_, rowIndex) => rowIndex !== index);
};

const fieldError = (index, field) => props.errors[`steps.${index}.${field}`];
</script>

<template>
    <div class="space-y-3 rounded border border-slate-200 p-3">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold">{{ trans('operation_sequences.steps.title') }}</h3>
            <Button type="button" :label="trans('operation_sequences.steps.add')" icon="pi pi-plus" size="small" outlined @click="addRow" />
        </div>

        <DataTable :value="rows" data-key="step_order" class="text-sm">
            <Column :header="trans('fields.order')">
                <template #body="{ data, index }">
                    <InputText
                        :model-value="data.step_order"
                        type="number"
                        class="w-20"
                        @update:model-value="updateRow(index, 'step_order', $event)"
                    />
                    <p v-if="fieldError(index, 'step_order')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'step_order') }}</p>
                </template>
            </Column>
            <Column :header="trans('fields.operation')">
                <template #body="{ data, index }">
                    <Select
                        :model-value="data.operation_type_id"
                        :options="operationTypeChoices"
                        option-label="label"
                        option-value="id"
                        filter
                        class="w-44"
                        @update:model-value="updateRow(index, 'operation_type_id', $event)"
                    />
                    <p v-if="fieldError(index, 'operation_type_id')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'operation_type_id') }}</p>
                </template>
            </Column>
            <Column :header="trans('fields.factory_unit')">
                <template #body="{ data, index }">
                    <Select
                        :model-value="data.factory_unit_id"
                        :options="factoryUnitChoices"
                        option-label="label"
                        option-value="id"
                        filter
                        class="w-40"
                        @update:model-value="updateRow(index, 'factory_unit_id', $event)"
                    />
                </template>
            </Column>
            <Column :header="trans('fields.professional_role')">
                <template #body="{ data, index }">
                    <Select
                        :model-value="data.professional_role_id"
                        :options="professionalRoleChoices"
                        option-label="label"
                        option-value="id"
                        filter
                        class="w-40"
                        @update:model-value="updateRow(index, 'professional_role_id', $event)"
                    />
                </template>
            </Column>
            <Column :header="trans('operation_sequences.fields.minutes')">
                <template #body="{ data, index }">
                    <InputText
                        :model-value="data.estimated_duration_minutes"
                        type="number"
                        class="w-24"
                        @update:model-value="updateRow(index, 'estimated_duration_minutes', $event)"
                    />
                </template>
            </Column>
            <Column :header="trans('operation_sequences.fields.quality_check')">
                <template #body="{ data, index }">
                    <Checkbox
                        :model-value="Boolean(data.requires_quality_check)"
                        binary
                        @update:model-value="updateRow(index, 'requires_quality_check', $event)"
                    />
                </template>
            </Column>
            <Column :header="trans('operation_sequences.fields.instructions')">
                <template #body="{ data, index }">
                    <Textarea
                        :model-value="data.instructions"
                        rows="1"
                        class="w-52"
                        @update:model-value="updateRow(index, 'instructions', $event)"
                    />
                </template>
            </Column>
            <Column header="" body-style="text-align: right; width: 4rem">
                <template #body="{ index }">
                    <Button type="button" icon="pi pi-trash" severity="danger" text rounded @click="removeRow(index)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
