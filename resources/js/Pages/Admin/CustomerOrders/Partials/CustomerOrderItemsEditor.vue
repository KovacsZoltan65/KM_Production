<script setup>
import UnitSelect from '@/Components/Admin/UnitSelect.vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    itemOptions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const rows = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const addRow = () => {
    rows.value = [...rows.value, { item_id: null, quantity: 1, unit: '', notes: '' }];
};

const updateRow = (index, key, value) => {
    const patch = { [key]: value };
    const selectedItem = key === 'item_id' ? props.itemOptions.find((item) => item.id === value) : null;

    if (selectedItem && !rows.value[index]?.unit) {
        patch.unit = selectedItem.unit;
    }

    rows.value = rows.value.map((row, rowIndex) => (rowIndex === index ? { ...row, ...patch } : row));
};

const removeRow = (index) => {
    rows.value = rows.value.filter((_, rowIndex) => rowIndex !== index);
};

const fieldError = (index, field) => props.errors[`items.${index}.${field}`];
</script>

<template>
    <div class="space-y-3 rounded border border-slate-200 p-3">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold">{{ trans('orders.items.title') }}</h3>
            <Button type="button" :label="trans('orders.items.add')" icon="pi pi-plus" size="small" outlined @click="addRow" />
        </div>

        <p v-if="errors.items" class="text-sm text-red-600">{{ errors.items }}</p>

        <DataTable :value="rows" class="text-sm">
            <Column :header="trans('fields.item')">
                <template #body="{ data, index }">
                    <Select
                        :model-value="data.item_id"
                        :options="itemOptions"
                        option-label="label"
                        option-value="id"
                        filter
                        class="w-full"
                        @update:model-value="updateRow(index, 'item_id', $event)"
                    />
                    <p v-if="fieldError(index, 'item_id')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'item_id') }}</p>
                </template>
            </Column>
            <Column :header="trans('fields.quantity')">
                <template #body="{ data, index }">
                    <InputText
                        :model-value="data.quantity"
                        type="number"
                        class="w-28"
                        @update:model-value="updateRow(index, 'quantity', $event)"
                    />
                    <p v-if="fieldError(index, 'quantity')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'quantity') }}</p>
                </template>
            </Column>
            <Column :header="trans('fields.unit')">
                <template #body="{ data, index }">
                    <UnitSelect
                        :model-value="data.unit"
                        class="w-24"
                        :invalid="Boolean(fieldError(index, 'unit'))"
                        required
                        @update:model-value="updateRow(index, 'unit', $event)"
                    />
                    <p v-if="fieldError(index, 'unit')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'unit') }}</p>
                </template>
            </Column>
            <Column :header="trans('fields.notes')">
                <template #body="{ data, index }">
                    <Textarea :model-value="data.notes" rows="1" class="w-full" @update:model-value="updateRow(index, 'notes', $event)" />
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
