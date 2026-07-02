<script setup>
import CustomerOrderItemsEditor from '@/Pages/Admin/CustomerOrders/Partials/CustomerOrderItemsEditor.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { trans } from 'laravel-vue-i18n';

defineProps({
    form: { type: Object, required: true },
    customerOptions: { type: Array, default: () => [] },
    itemOptions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    submitLabel: { type: String, default: '' },
});

defineEmits(['submit', 'cancel']);
</script>

<template>
    <form class="space-y-4" @submit.prevent="$emit('submit')">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <label for="customer_id" class="text-sm font-medium">{{ trans('fields.customer') }}</label>
                <Select
                    id="customer_id"
                    v-model="form.customer_id"
                    :options="customerOptions"
                    option-label="label"
                    option-value="id"
                    filter
                    class="w-full"
                />
                <p v-if="errors.customer_id" class="text-sm text-red-600">{{ errors.customer_id }}</p>
            </div>
            <div class="space-y-2">
                <label for="requested_delivery_date" class="text-sm font-medium">{{ trans('orders.fields.requested_delivery_date') }}</label>
                <InputText id="requested_delivery_date" v-model="form.requested_delivery_date" type="date" class="w-full" />
                <p v-if="errors.requested_delivery_date" class="text-sm text-red-600">{{ errors.requested_delivery_date }}</p>
            </div>
        </div>

        <div class="space-y-2">
            <label for="notes" class="text-sm font-medium">{{ trans('fields.notes') }}</label>
            <Textarea id="notes" v-model="form.notes" rows="3" class="w-full" />
            <p v-if="errors.notes" class="text-sm text-red-600">{{ errors.notes }}</p>
        </div>

        <CustomerOrderItemsEditor v-model="form.items" :item-options="itemOptions" :errors="errors" />

        <div class="flex justify-end gap-2 pt-2">
            <Button type="button" :label="trans('actions.cancel')" severity="secondary" outlined @click="$emit('cancel')" />
            <Button type="submit" :label="submitLabel || trans('actions.save')" icon="pi pi-save" />
        </div>
    </form>
</template>
