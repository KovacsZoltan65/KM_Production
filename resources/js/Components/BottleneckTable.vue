<script setup>
import RiskBadge from "@/Components/RiskBadge.vue";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import ProgressBar from "primevue/progressbar";

/**
 * Kapacitási szűk keresztmetszet sora.
 * @typedef {Object} BottleneckRow
 * @property {string} factory_unit A gyártóegység neve.
 * @property {number} reserved_minutes A lefoglalt idő percben.
 * @property {number} available_minutes A rendelkezésre álló idő percben.
 * @property {number} utilization_percent A kihasználtság százalékban.
 * @property {number} queue_length A várakozó feladatok száma.
 * @property {number} average_task_duration Az átlagos feladatidő percben.
 * @property {number} late_related_orders A kapcsolódó késő rendelések száma.
 * @property {string} status A kockázati állapot.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {BottleneckRow[]} rows A megjelenített szűk keresztmetszetek.
 */
/** @type {Props} */
defineProps({
    rows: { type: Array, default: () => [] },
});
</script>

<template>
    <DataTable :value="rows" class="rounded border border-slate-200 bg-white">
        <Column
            field="factory_unit"
            :header="$t('reports.columns.factory_unit')"
            sortable
        />
        <Column
            field="reserved_minutes"
            :header="$t('intelligence.columns.reserved_minutes')"
            sortable
        />
        <Column
            field="available_minutes"
            :header="$t('intelligence.columns.available_minutes')"
            sortable
        />
        <Column
            field="utilization_percent"
            :header="$t('intelligence.columns.utilization')"
            sortable
        >
            <template #body="{ data }">
                <div class="min-w-40">
                    <ProgressBar :value="Number(data.utilization_percent)" />
                    <div class="mt-1 text-xs text-slate-600">
                        {{ data.utilization_percent }}%
                    </div>
                </div>
            </template>
        </Column>
        <Column
            field="queue_length"
            :header="$t('intelligence.columns.queue')"
            sortable
        />
        <Column
            field="average_task_duration"
            :header="$t('intelligence.columns.avg_task_min')"
            sortable
        />
        <Column
            field="late_related_orders"
            :header="$t('intelligence.columns.late_orders')"
            sortable
        />
        <Column field="status" :header="$t('fields.status')" sortable>
            <template #body="{ data }"
                ><RiskBadge :value="data.status"
            /></template>
        </Column>
    </DataTable>
</template>
