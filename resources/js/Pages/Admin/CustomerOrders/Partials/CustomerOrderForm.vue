<script setup>
import CustomerOrderItemsEditor from "@/Pages/Admin/CustomerOrders/Partials/CustomerOrderItemsEditor.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import { trans } from "laravel-vue-i18n";

/** @typedef {{item_id: number|null, quantity: number|string, unit: string, notes: string}} CustomerOrderItemInput */
/** @typedef {{id: number, code: string, name: string, label: string}} CustomerOption */
/** @typedef {{id: number, item_number: string, name: string, unit: string, label: string}} ItemOption */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ customer_id: number|null, items: CustomerOrderItemInput[], notes: string, requested_delivery_date: string|null }} form A vevői rendelés űrlapállapota.
 * @property {CustomerOption[]} customerOptions A választható vevők.
 * @property {ItemOption[]} itemOptions A választható cikkek.
 * @property {Object.<string, string>} errors Az űrlap validációs hibái.
 * @property {boolean} processing Jelzi, hogy az űrlap mentése folyamatban van.
 * @property {string} submitLabel A mentés gombfelirata.
 */
/** @type {Props} */
defineProps({
    form: { type: Object, required: true },
    customerOptions: { type: Array, default: () => [] },
    itemOptions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    processing: { type: Boolean, default: false },
    submitLabel: { type: String, default: "" },
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
                <label for="customer_id" class="text-sm font-medium">{{
                    trans("fields.customer")
                }}</label>
                <Select
                    id="customer_id"
                    v-model="form.customer_id"
                    :options="customerOptions"
                    option-label="label"
                    option-value="id"
                    :aria-label="trans('fields.customer')"
                    filter
                    class="w-full"
                />
                <p v-if="errors.customer_id" class="text-sm text-red-600">
                    {{ errors.customer_id }}
                </p>
            </div>
            <div class="space-y-2">
                <label
                    for="requested_delivery_date"
                    class="text-sm font-medium"
                    >{{ trans("orders.fields.requested_delivery_date") }}</label
                >
                <InputText
                    id="requested_delivery_date"
                    v-model="form.requested_delivery_date"
                    type="date"
                    class="w-full"
                />
                <p
                    v-if="errors.requested_delivery_date"
                    class="text-sm text-red-600"
                >
                    {{ errors.requested_delivery_date }}
                </p>
            </div>
        </div>

        <div class="space-y-2">
            <label for="notes" class="text-sm font-medium">{{
                trans("fields.notes")
            }}</label>
            <Textarea id="notes" v-model="form.notes" rows="3" class="w-full" />
            <p v-if="errors.notes" class="text-sm text-red-600">
                {{ errors.notes }}
            </p>
        </div>

        <CustomerOrderItemsEditor
            v-model="form.items"
            :item-options="itemOptions"
            :errors="errors"
        />

        <div class="flex justify-end gap-2 pt-2">
            <Button
                type="button"
                :label="trans('actions.cancel')"
                severity="secondary"
                outlined
                @click="$emit('cancel')"
            />
            <Button
                type="submit"
                :label="submitLabel || trans('actions.save')"
                icon="pi pi-save"
                :loading="processing"
                :disabled="processing"
            />
        </div>
    </form>
</template>
