<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

/**
 * Műhelyriport sora.
 * @typedef {Object} ShopFloorReportRow
 * @property {string} employee A munkatárs neve.
 * @property {number} open_tasks A nyitott feladatok száma.
 * @property {number} in_progress A folyamatban lévő feladatok száma.
 * @property {number} completed_today A ma befejezett feladatok száma.
 * @property {number|null} average_task_time Az átlagos feladatidő percben.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ rows: ShopFloorReportRow[] }} report A műhelyriport.
 */
/** @type {Props} */
defineProps({ report: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('reports.shop_floor.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("reports.shop_floor.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("reports.shop_floor.subtitle") }}
                </p>
            </div>
            <DataTable
                :value="report.rows"
                data-key="employee"
                class="rounded border border-slate-200 bg-white"
            >
                <Column
                    field="employee"
                    :header="$t('fields.employee')"
                    sortable
                />
                <Column
                    field="open_tasks"
                    :header="$t('reports.columns.open_tasks')"
                    sortable
                />
                <Column
                    field="in_progress"
                    :header="$t('reports.columns.in_progress')"
                    sortable
                />
                <Column
                    field="completed_today"
                    :header="$t('reports.columns.completed_today')"
                    sortable
                />
                <Column
                    field="average_task_time"
                    :header="$t('reports.columns.average_task_time')"
                    sortable
                >
                    <template #body="{ data }">{{
                        data.average_task_time === null
                            ? "-"
                            : $t("reports.units.minutes", {
                                  value: data.average_task_time,
                              })
                    }}</template>
                </Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
