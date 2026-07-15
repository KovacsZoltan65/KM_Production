<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import SimulationResultCard from "@/Components/SimulationResultCard.vue";
import { route } from "@/Utils/routes";
import { router } from "@inertiajs/vue3";
import Button from "primevue/button";
import Select from "primevue/select";
import { ref } from "vue";

defineOptions({ layout: AdminLayout });

/**
 * Választható vevői rendelés.
 * @typedef {Object} CustomerOrderOption
 * @property {number} id A vevői rendelés azonosítója.
 * @property {string} label A rendelés megjelenített felirata.
 */

/**
 * Kapacitásszimuláció eredménye.
 * @typedef {Object} SimulationResult
 * @property {string} estimatedStart A becsült kezdés.
 * @property {string} estimatedFinish A becsült befejezés.
 * @property {boolean} isLate Jelzi a várható késést.
 * @property {number} lateByMinutes A várható késés percben.
 * @property {string} criticalFactoryUnit A kritikus gyártóegység.
 * @property {string} criticalProfessionalRole A kritikus szakmai szerepkör.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {CustomerOrderOption[]} customerOrders A választható vevői rendelések.
 * @property {number|null} [selectedCustomerOrderId] A kiválasztott rendelés azonosítója.
 * @property {SimulationResult|null} result A szimuláció eredménye.
 */
/** @type {Props} */
const props = defineProps({
    customerOrders: { type: Array, default: () => [] },
    selectedCustomerOrderId: { type: Number, default: null },
    result: { type: Object, default: null },
});

const selectedCustomerOrderId = ref(props.selectedCustomerOrderId);

const simulate = () => {
    if (!selectedCustomerOrderId.value) return;

    router.post(route("admin.capacity.simulate.run"), {
        customer_order_id: selectedCustomerOrderId.value,
    });
};
</script>

<template>
    <section class="space-y-5">
        <div>
            <h1 class="text-2xl font-semibold">
                {{ $t("capacity.simulation.title") }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ $t("capacity.simulation.subtitle") }}
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <Select
                v-model="selectedCustomerOrderId"
                :options="props.customerOrders"
                optionLabel="label"
                optionValue="id"
                :placeholder="$t('fields.customer_order')"
                class="w-full sm:w-72"
            />
            <Button
                :label="$t('capacity.actions.simulate')"
                icon="pi pi-chart-line"
                :disabled="!selectedCustomerOrderId"
                @click="simulate"
            />
        </div>

        <SimulationResultCard :result="result" />
    </section>
</template>
