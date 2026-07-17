<script setup>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import { loadStatusSeverity } from "@/Utils/dashboard";

/**
 * Gyártóegység kapacitásterhelése.
 * @typedef {Object} FactoryLoad
 * @property {string} factory_unit A gyártóegység neve.
 * @property {number} available_minutes A rendelkezésre álló idő percben.
 * @property {number} reserved_minutes A lefoglalt idő percben.
 * @property {number} utilization A kihasználtság százalékban.
 * @property {'green'|'yellow'|'red'|string} status A terhelési állapot.
 * @property {number} current_queue A várakozó feladatok száma.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {FactoryLoad[]} loads A megjelenített gyártóegység-terhelések.
 */
/** @type {Props} */
defineProps({
    loads: { type: Array, default: () => [] },
});

const severityFor = loadStatusSeverity;
</script>

<template>
    <DataTable
        :value="loads"
        stripedRows
        size="small"
        responsiveLayout="scroll"
    >
        <Column field="factory_unit" header="Factory Unit" />
        <Column field="available_minutes" header="Available Minutes" />
        <Column field="reserved_minutes" header="Reserved Minutes" />
        <Column field="utilization" header="Utilization %">
            <template #body="{ data }">
                <Tag
                    :value="`${data.utilization}%`"
                    :severity="severityFor(data.status)"
                />
            </template>
        </Column>
        <Column field="current_queue" header="Current Queue" />
    </DataTable>
</template>
