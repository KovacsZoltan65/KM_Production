<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ScheduleTimeline from "@/Components/ScheduleTimeline.vue";
import { route } from "@/Utils/routes";
import { router } from "@inertiajs/vue3";
import Button from "primevue/button";
import Select from "primevue/select";
import { ref } from "vue";

defineOptions({ layout: AdminLayout });

/**
 * Ütemezési sor.
 * @typedef {Object} ScheduleRow
 * @property {number} id A kapacitásfoglalás azonosítója.
 * @property {string} factory_unit A gyártóegység neve.
 * @property {string} production_task A gyártási feladat megnevezése.
 * @property {string} start A kezdés időpontja.
 * @property {string} finish A befejezés időpontja.
 * @property {number} duration Az időtartam percben.
 * @property {string} employee A kijelölt munkatárs neve.
 * @property {string} status Az ütemezési állapot.
 */

/**
 * Választható gyártási rendelés.
 * @typedef {Object} ProductionOrderOption
 * @property {number} id A gyártási rendelés azonosítója.
 * @property {string} label A rendelés megjelenített felirata.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {ScheduleRow[]} rows Az ütemezési sorok.
 * @property {ProductionOrderOption[]} productionOrders A választható gyártási rendelések.
 * @property {boolean} canPlan Rendelkezik-e a felhasználó tervezési jogosultsággal.
 */
/** @type {Props} */
const props = defineProps({
    rows: { type: Array, default: () => [] },
    productionOrders: { type: Array, default: () => [] },
    canPlan: { type: Boolean, default: false },
});

const selectedProductionOrderId = ref(null);

const generate = () => {
    if (!selectedProductionOrderId.value) return;

    router.post(route("admin.capacity.schedule.store"), {
        production_order_id: selectedProductionOrderId.value,
    });
};
</script>

<template>
    <section class="space-y-5">
        <div
            class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("capacity.schedule.title") }}
                </h1>
                <p class="text-sm text-slate-500">
                    {{ $t("capacity.schedule.subtitle") }}
                </p>
            </div>
            <div v-if="canPlan" class="flex flex-col gap-2 sm:flex-row">
                <Select
                    v-model="selectedProductionOrderId"
                    :options="props.productionOrders"
                    optionLabel="label"
                    optionValue="id"
                    :placeholder="$t('fields.production_order')"
                    class="w-full sm:w-64"
                />
                <Button
                    :label="$t('actions.generate')"
                    icon="pi pi-calendar-plus"
                    :disabled="!selectedProductionOrderId"
                    @click="generate"
                />
            </div>
        </div>

        <ScheduleTimeline :rows="rows" />
    </section>
</template>
