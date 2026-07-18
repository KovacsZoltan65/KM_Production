<script setup>
import UnitSelect from "@/Components/Admin/UnitSelect.vue";
import { route } from "@/Utils/routes";
import { useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import { watch } from "vue";

/** @typedef {{id: number, unit: string, label: string}} ItemOption */
/** @typedef {{id: number, label: string}} LocationOption */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ id: number }} productionTask A gyártási feladat azonosítója.
 * @property {ItemOption[]} itemOptions A választható cikkek.
 * @property {LocationOption[]} locationOptions A választható raktárhelyek.
 */
/** @type {Props} */
const props = defineProps({
    productionTask: Object,
    itemOptions: Array,
    locationOptions: Array,
});

const form = useForm({
    item_id: null,
    item_batch_id: null,
    location_id: null,
    planned_quantity: 0,
    used_quantity: 1,
    unit: "",
    notes: "",
});

watch(
    () => form.item_id,
    (itemId) => {
        const item = props.itemOptions.find((option) => option.id === itemId);
        form.unit = item?.unit || form.unit;
    },
);

const submit = () => {
    form.post(
        route(
            "admin.production-tasks.materials.store",
            props.productionTask.id,
        ),
        {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        },
    );
};
</script>

<template>
    <form
        class="grid gap-3 rounded border border-slate-200 bg-white p-4 md:grid-cols-2"
        @submit.prevent="submit"
    >
        <Select
            v-model="form.item_id"
            :options="itemOptions"
            option-label="label"
            option-value="id"
            :aria-label="$t('fields.material')"
            :placeholder="$t('fields.material')"
            filter
        />
        <Select
            v-model="form.location_id"
            :options="locationOptions"
            option-label="label"
            option-value="id"
            :aria-label="$t('fields.stock_location')"
            :placeholder="$t('fields.stock_location')"
            filter
            show-clear
        />
        <InputNumber
            v-model="form.planned_quantity"
            :min-fraction-digits="3"
            :max-fraction-digits="3"
            :input-props="{ 'aria-label': $t('fields.planned_quantity') }"
            :placeholder="$t('fields.planned_quantity')"
        />
        <InputNumber
            v-model="form.used_quantity"
            :min-fraction-digits="3"
            :max-fraction-digits="3"
            :input-props="{ 'aria-label': $t('fields.used_quantity') }"
            :placeholder="$t('fields.used_quantity')"
        />
        <div>
            <UnitSelect
                v-model="form.unit"
                :placeholder="$t('fields.unit')"
                :invalid="Boolean(form.errors.unit)"
                required
            />
            <p v-if="form.errors.unit" class="mt-1 text-sm text-red-600">
                {{ form.errors.unit }}
            </p>
        </div>
        <Textarea
            v-model="form.notes"
            rows="1"
            :aria-label="$t('fields.notes')"
            :placeholder="$t('fields.notes')"
            class="md:col-span-2"
        />
        <div class="md:col-span-2">
            <Button
                type="submit"
                :label="$t('production.materials.record')"
                icon="pi pi-minus-circle"
                :loading="form.processing"
            />
        </div>
    </form>
</template>
