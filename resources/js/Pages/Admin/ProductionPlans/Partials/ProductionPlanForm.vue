<script setup>
import ProductionPlanItemsEditor from "@/Pages/Admin/ProductionPlans/Partials/ProductionPlanItemsEditor.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Textarea from "primevue/textarea";

/** @typedef {{id: number, order_number: string, customer_name: string, label: string}} CustomerOrderOption */
/** @typedef {{id: number, item_id: number, label: string}} ItemDefinitionOption */
/** @typedef {{id?: number, item_id: number, item_label: string, quantity: number|string, bom_id: number|null, operation_sequence_id: number|null, planned_start_date: string|null, planned_finish_date: string|null, status: string, notes: string|null}} ProductionPlanItemInput */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ customer_order_id: number|null, items: ProductionPlanItemInput[], notes: string, planned_finish_date: string|null, planned_start_date: string|null }} form A gyártási terv űrlapállapota.
 * @property {CustomerOrderOption[]} customerOrderOptions A választható vevői rendelések.
 * @property {ItemDefinitionOption[]} bomOptions A választható darabjegyzékek.
 * @property {ItemDefinitionOption[]} operationSequenceOptions A választható műveletsorok.
 * @property {Object.<string, string>} errors Az űrlap validációs hibái.
 * @property {boolean} isEditing Jelzi a szerkesztési állapotot.
 */
/** @type {Props} */
defineProps({
    form: { type: Object, required: true },
    customerOrderOptions: { type: Array, default: () => [] },
    bomOptions: { type: Array, default: () => [] },
    operationSequenceOptions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    isEditing: { type: Boolean, default: false },
});

/**
 * A komponens által kibocsátott események.
 * @typedef {Object} Emits
 * @property {(event: 'submit') => void} submit A(z) submit esemény.
 * @property {(event: 'cancel') => void} cancel A(z) cancel esemény.
 */
/** @type {Emits} */
defineEmits(["submit", "cancel"]);
</script>

<template>
    <form class="space-y-4" @submit.prevent="$emit('submit')">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <label for="customer_order_id" class="text-sm font-medium">{{
                    $t("fields.customer_order")
                }}</label>
                <Select
                    id="customer_order_id"
                    v-model="form.customer_order_id"
                    :options="customerOrderOptions"
                    option-label="label"
                    option-value="id"
                    :aria-label="$t('fields.customer_order')"
                    :disabled="isEditing"
                    filter
                    class="w-full"
                />
                <p v-if="errors.customer_order_id" class="text-sm text-red-600">
                    {{ errors.customer_order_id }}
                </p>
            </div>
            <div class="space-y-2">
                <label for="planned_start_date" class="text-sm font-medium">{{
                    $t("fields.planned_start")
                }}</label>
                <InputText
                    id="planned_start_date"
                    v-model="form.planned_start_date"
                    type="date"
                    class="w-full"
                />
            </div>
            <div class="space-y-2">
                <label for="planned_finish_date" class="text-sm font-medium">{{
                    $t("fields.planned_finish")
                }}</label>
                <InputText
                    id="planned_finish_date"
                    v-model="form.planned_finish_date"
                    type="date"
                    class="w-full"
                />
                <p
                    v-if="errors.planned_finish_date"
                    class="text-sm text-red-600"
                >
                    {{ errors.planned_finish_date }}
                </p>
            </div>
        </div>

        <div class="space-y-2">
            <label for="notes" class="text-sm font-medium">{{
                $t("fields.notes")
            }}</label>
            <Textarea id="notes" v-model="form.notes" rows="3" class="w-full" />
        </div>

        <ProductionPlanItemsEditor
            v-if="isEditing"
            v-model="form.items"
            :bom-options="bomOptions"
            :operation-sequence-options="operationSequenceOptions"
            :errors="errors"
        />

        <div class="flex justify-end gap-2 pt-2">
            <Button
                type="button"
                :label="$t('actions.cancel')"
                severity="secondary"
                outlined
                @click="$emit('cancel')"
            />
            <Button
                type="submit"
                :label="$t('actions.save')"
                icon="pi pi-save"
            />
        </div>
    </form>
</template>
