<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import FactoryLoadTable from "@/Components/FactoryLoadTable.vue";

defineOptions({ layout: AdminLayout });

/**
 * Gyártóegység kapacitásterhelése.
 * @typedef {Object} FactoryLoad
 * @property {number} id A gyártóegység azonosítója.
 * @property {string} factory_unit A gyártóegység neve.
 * @property {string} code A gyártóegység kódja.
 * @property {number} available_minutes A rendelkezésre álló idő percben.
 * @property {number} reserved_minutes A lefoglalt idő percben.
 * @property {number} utilization A kihasználtság százalékban.
 * @property {number} current_queue A várakozó feladatok száma.
 * @property {string} status A terhelési állapot.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {FactoryLoad[]} loads A gyártóegységek kapacitásterhelése.
 */
/** @type {Props} */
defineProps({
    loads: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="space-y-5">
        <div>
            <h1 class="text-2xl font-semibold">
                {{ $t("capacity.factory_units.title") }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ $t("capacity.factory_units.subtitle") }}
            </p>
        </div>
        <FactoryLoadTable :loads="loads" />
    </section>
</template>
