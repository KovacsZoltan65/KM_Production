<script setup>
import ProductionPlanStatusBadge from '@/Pages/Admin/ProductionPlans/Partials/ProductionPlanStatusBadge.vue';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    bomOptions: { type: Array, default: () => [] },
    operationSequenceOptions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const rows = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const updateRow = (index, key, value) => {
    rows.value = rows.value.map((row, rowIndex) => (rowIndex === index ? { ...row, [key]: value } : row));
};

const fieldError = (index, field) => props.errors[`items.${index}.${field}`];
const optionsForItem = (options, itemId) => options.filter((option) => option.item_id === itemId);
</script>

<template>
    <div class="space-y-3 rounded border border-slate-200 p-3">
        <h3 class="text-sm font-semibold">Production plan items</h3>
        <DataTable :value="rows" class="text-sm">
            <Column header="Item">
                <template #body="{ data }">
                    <div class="font-medium">{{ data.item_label }}</div>
                    <div class="text-xs text-slate-500">{{ data.quantity }}</div>
                </template>
            </Column>
            <Column header="BOM">
                <template #body="{ data, index }">
                    <Select
                        :model-value="data.bom_id"
                        :options="optionsForItem(bomOptions, data.item_id)"
                        option-label="label"
                        option-value="id"
                        show-clear
                        class="w-full min-w-48"
                        @update:model-value="updateRow(index, 'bom_id', $event)"
                    />
                    <p v-if="fieldError(index, 'bom_id')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'bom_id') }}</p>
                </template>
            </Column>
            <Column header="Operation sequence">
                <template #body="{ data, index }">
                    <Select
                        :model-value="data.operation_sequence_id"
                        :options="optionsForItem(operationSequenceOptions, data.item_id)"
                        option-label="label"
                        option-value="id"
                        show-clear
                        class="w-full min-w-48"
                        @update:model-value="updateRow(index, 'operation_sequence_id', $event)"
                    />
                    <p v-if="fieldError(index, 'operation_sequence_id')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'operation_sequence_id') }}</p>
                </template>
            </Column>
            <Column header="Start">
                <template #body="{ data, index }">
                    <InputText
                        :model-value="data.planned_start_date"
                        type="date"
                        class="w-40"
                        @update:model-value="updateRow(index, 'planned_start_date', $event)"
                    />
                </template>
            </Column>
            <Column header="Finish">
                <template #body="{ data, index }">
                    <InputText
                        :model-value="data.planned_finish_date"
                        type="date"
                        class="w-40"
                        @update:model-value="updateRow(index, 'planned_finish_date', $event)"
                    />
                </template>
            </Column>
            <Column header="Status">
                <template #body="{ data }"><ProductionPlanStatusBadge :status="data.status" /></template>
            </Column>
            <Column header="Notes">
                <template #body="{ data, index }">
                    <Textarea :model-value="data.notes" rows="1" class="w-full" @update:model-value="updateRow(index, 'notes', $event)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
