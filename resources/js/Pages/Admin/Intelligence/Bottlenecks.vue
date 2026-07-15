<script setup>
import BottleneckTable from "@/Components/BottleneckTable.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

/**
 * Kapacitási szűk keresztmetszet.
 * @typedef {Object} BottleneckRow
 * @property {string} factory_unit A gyártóegység megnevezése.
 * @property {number} reserved_minutes A lefoglalt idő percben.
 * @property {number} available_minutes A rendelkezésre álló idő percben.
 * @property {number} utilization_percent A kihasználtság százalékban.
 * @property {number} queue_length A várakozó feladatok száma.
 * @property {number|null} average_task_duration Az átlagos feladatidő percben.
 * @property {number} late_related_orders A kapcsolódó késő rendelések száma.
 * @property {string} status A kockázati állapot.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ rows: BottleneckRow[] }} analysis A szűk keresztmetszetek elemzése.
 */
/** @type {Props} */
defineProps({ analysis: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.bottlenecks.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.bottlenecks.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.bottlenecks.subtitle") }}
                </p>
            </div>
            <BottleneckTable :rows="analysis.rows" />
        </div>
    </AdminLayout>
</template>
