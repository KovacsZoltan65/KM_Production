<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SimulationResultCard from '@/Components/SimulationResultCard.vue';
import { route } from '@/Utils/routes';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    customerOrders: { type: Array, default: () => [] },
    selectedCustomerOrderId: { type: Number, default: null },
    result: { type: Object, default: null },
});

const selectedCustomerOrderId = ref(props.selectedCustomerOrderId);

const simulate = () => {
    if (!selectedCustomerOrderId.value) return;

    router.post(route('admin.capacity.simulate.run'), {
        customer_order_id: selectedCustomerOrderId.value,
    });
};
</script>

<template>
    <section class="space-y-5">
        <div>
            <h1 class="text-2xl font-semibold">Capacity Simulation</h1>
            <p class="text-sm text-slate-500">Readonly lead time estimate for customer orders.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <Select
                v-model="selectedCustomerOrderId"
                :options="props.customerOrders"
                optionLabel="label"
                optionValue="id"
                placeholder="Customer Order"
                class="w-full sm:w-72"
            />
            <Button label="Simulate" icon="pi pi-chart-line" :disabled="!selectedCustomerOrderId" @click="simulate" />
        </div>

        <SimulationResultCard :result="result" />
    </section>
</template>
