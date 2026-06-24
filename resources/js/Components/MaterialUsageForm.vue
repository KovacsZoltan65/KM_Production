<script setup>
import { route } from '@/Utils/routes';
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { watch } from 'vue';

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
    unit: '',
    notes: '',
});

watch(
    () => form.item_id,
    (itemId) => {
        const item = props.itemOptions.find((option) => option.id === itemId);
        form.unit = item?.unit || form.unit;
    },
);

const submit = () => {
    form.post(route('admin.production-tasks.materials.store', props.productionTask.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <form class="grid gap-3 rounded border border-slate-200 bg-white p-4 md:grid-cols-2" @submit.prevent="submit">
        <Select v-model="form.item_id" :options="itemOptions" option-label="label" option-value="id" placeholder="Material" filter />
        <Select v-model="form.location_id" :options="locationOptions" option-label="label" option-value="id" placeholder="Stock location" filter show-clear />
        <InputNumber v-model="form.planned_quantity" :min-fraction-digits="3" :max-fraction-digits="3" placeholder="Planned quantity" />
        <InputNumber v-model="form.used_quantity" :min-fraction-digits="3" :max-fraction-digits="3" placeholder="Used quantity" />
        <InputText v-model="form.unit" placeholder="Unit" />
        <Textarea v-model="form.notes" rows="1" placeholder="Notes" class="md:col-span-2" />
        <div class="md:col-span-2">
            <Button type="submit" label="Record material" icon="pi pi-minus-circle" :loading="form.processing" />
        </div>
    </form>
</template>
