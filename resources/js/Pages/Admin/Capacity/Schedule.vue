<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ScheduleTimeline from '@/Components/ScheduleTimeline.vue';
import { route } from '@/Utils/routes';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    rows: { type: Array, default: () => [] },
    productionOrders: { type: Array, default: () => [] },
    canPlan: { type: Boolean, default: false },
});

const selectedProductionOrderId = ref(null);

const generate = () => {
    if (!selectedProductionOrderId.value) return;

    router.post(route('admin.capacity.schedule.store'), {
        production_order_id: selectedProductionOrderId.value,
    });
};
</script>

<template>
    <section class="space-y-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Capacity Schedule</h1>
                <p class="text-sm text-slate-500">Gantt-like task reservations by factory unit.</p>
            </div>
            <div v-if="canPlan" class="flex flex-col gap-2 sm:flex-row">
                <Select
                    v-model="selectedProductionOrderId"
                    :options="props.productionOrders"
                    optionLabel="label"
                    optionValue="id"
                    placeholder="Production Order"
                    class="w-full sm:w-64"
                />
                <Button label="Generate" icon="pi pi-calendar-plus" :disabled="!selectedProductionOrderId" @click="generate" />
            </div>
        </div>

        <ScheduleTimeline :rows="rows" />
    </section>
</template>
