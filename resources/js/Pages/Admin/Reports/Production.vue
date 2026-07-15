<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import ProgressBar from "primevue/progressbar";
import Tag from "primevue/tag";

/**
 * Gyártási riport sora.
 * @typedef {Object} ProductionReportRow
 * @property {number} id A gyártási rendelés azonosítója.
 * @property {string} production_order A gyártási rendelés száma.
 * @property {string} product A termék neve.
 * @property {string} status A rendelés állapota.
 * @property {string} factory_unit A gyártóegység neve.
 * @property {string|null} planned_start A tervezett kezdés.
 * @property {string|null} planned_finish A tervezett befejezés.
 * @property {number} completed_tasks A befejezett feladatok száma.
 * @property {number} all_tasks Az összes feladat száma.
 * @property {number} completed_percent A készültség százalékban.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ rows: ProductionReportRow[] }} report A gyártási riport.
 */
/** @type {Props} */
defineProps({ report: { type: Object, required: true } });
const typeLabel = (value) => String(value || "").replaceAll("_", " ");
</script>

<template>
    <Head :title="$t('reports.production.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("reports.production.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("reports.production.subtitle") }}
                </p>
            </div>
            <DataTable
                :value="report.rows"
                data-key="id"
                class="rounded border border-slate-200 bg-white"
            >
                <Column
                    field="production_order"
                    :header="$t('fields.production_order')"
                    sortable
                />
                <Column
                    field="product"
                    :header="$t('fields.product')"
                    sortable
                />
                <Column field="status" :header="$t('fields.status')" sortable
                    ><template #body="{ data }"
                        ><Tag
                            :value="$t(`status.${data.status}`)"
                            class="capitalize" /></template
                ></Column>
                <Column
                    field="factory_unit"
                    :header="$t('reports.columns.factory_unit')"
                />
                <Column
                    field="planned_start"
                    :header="$t('reports.columns.planned_start')"
                    sortable
                />
                <Column
                    field="planned_finish"
                    :header="$t('reports.columns.planned_finish')"
                    sortable
                />
                <Column
                    field="completed_percent"
                    :header="$t('reports.columns.completed_tasks_percent')"
                    sortable
                >
                    <template #body="{ data }">
                        <div class="min-w-40">
                            <ProgressBar
                                :value="Number(data.completed_percent)"
                            />
                            <div class="mt-1 text-xs text-slate-600">
                                {{ data.completed_tasks }} /
                                {{ data.all_tasks }}
                            </div>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
