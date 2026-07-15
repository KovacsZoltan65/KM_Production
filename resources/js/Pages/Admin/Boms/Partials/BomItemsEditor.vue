<script setup>
import UnitSelect from "@/Components/Admin/UnitSelect.vue";
import Button from "primevue/button";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

/** @typedef {{item_id: number|null, quantity: number|string, unit: string, notes: string}} BomItemInput */
/** @typedef {{id: number, item_number: string, name: string, unit: string}} ItemOption */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {BomItemInput[]} modelValue A szerkesztett darabjegyzéksorok.
 * @property {ItemOption[]} itemOptions A választható cikkek.
 * @property {Object.<string, string>} errors A tételmezők validációs hibái.
 */
/** @type {Props} */
const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    itemOptions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

/**
 * A komponens által kibocsátott események.
 * @typedef {Object} Emits
 * @property {(event: 'update:modelValue', value: BomItemInput[]) => void} updateModelValue A darabjegyzéksorok módosítási eseménye.
 */
/** @type {Emits} */
const emit = defineEmits(["update:modelValue"]);

const rows = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const itemChoices = computed(() =>
    props.itemOptions.map((item) => ({
        ...item,
        label: `${item.item_number} - ${item.name}`,
    })),
);

const addRow = () => {
    rows.value = [
        ...rows.value,
        { item_id: null, quantity: 1, unit: "", notes: "" },
    ];
};

const updateRow = (index, key, value) => {
    rows.value = rows.value.map((row, rowIndex) =>
        rowIndex === index ? { ...row, [key]: value } : row,
    );
};

const removeRow = (index) => {
    rows.value = rows.value.filter((_, rowIndex) => rowIndex !== index);
};

const fieldError = (index, field) => props.errors[`items.${index}.${field}`];
</script>

<template>
    <div class="space-y-3 rounded border border-slate-200 p-3">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold">
                {{ trans("bom.items.title") }}
            </h3>
            <Button
                type="button"
                :label="trans('bom.items.add')"
                icon="pi pi-plus"
                size="small"
                outlined
                @click="addRow"
            />
        </div>

        <DataTable :value="rows" data-key="item_id" class="text-sm">
            <!-- Alapanyag -->
            <Column :header="trans('fields.item')">
                <template #body="{ data, index }">
                    <Select
                        :model-value="data.item_id"
                        :options="itemChoices"
                        option-label="label"
                        option-value="id"
                        filter
                        class="w-full"
                        @update:model-value="
                            updateRow(index, 'item_id', $event)
                        "
                    />
                    <p
                        v-if="fieldError(index, 'item_id')"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ fieldError(index, "item_id") }}
                    </p>
                </template>
            </Column>

            <!-- Mennyiség -->
            <Column :header="trans('fields.quantity')">
                <template #body="{ data, index }">
                    <InputText
                        :model-value="data.quantity"
                        type="number"
                        class="w-24"
                        @update:model-value="
                            updateRow(index, 'quantity', $event)
                        "
                    />
                    <p
                        v-if="fieldError(index, 'quantity')"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ fieldError(index, "quantity") }}
                    </p>
                </template>
            </Column>

            <!-- Mértékegység -->
            <Column :header="trans('fields.unit')">
                <template #body="{ data, index }">
                    <UnitSelect
                        :model-value="data.unit"
                        class="w-24"
                        :invalid="Boolean(fieldError(index, 'unit'))"
                        required
                        @update:model-value="updateRow(index, 'unit', $event)"
                    />
                    <p
                        v-if="fieldError(index, 'unit')"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ fieldError(index, "unit") }}
                    </p>
                </template>
            </Column>

            <!-- Megjegyzés -->
            <Column :header="trans('fields.notes')">
                <template #body="{ data, index }">
                    <Textarea
                        :model-value="data.notes"
                        rows="1"
                        class="w-full"
                        @update:model-value="updateRow(index, 'notes', $event)"
                    />
                </template>
            </Column>

            <!-- Sorműveletek -->
            <Column header="" body-style="text-align: right; width: 4rem">
                <template #body="{ index }">
                    <Button
                        type="button"
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        rounded
                        @click="removeRow(index)"
                    />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
