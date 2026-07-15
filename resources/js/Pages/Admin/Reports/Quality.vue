<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import ProgressBar from "primevue/progressbar";

/**
 * Minőségügyi riport sora.
 * @typedef {Object} QualityReportRow
 * @property {string} production_order A gyártási rendelés száma.
 * @property {number} quality_checks A minőségellenőrzések száma.
 * @property {number} accepted Az elfogadott ellenőrzések száma.
 * @property {number} rejected Az elutasított ellenőrzések száma.
 * @property {number} rework Az utómunkát igénylő ellenőrzések száma.
 * @property {number} acceptance_rate Az elfogadási arány százalékban.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ rows: QualityReportRow[] }} report A minőségügyi riport.
 */
/** @type {Props} */
defineProps({ report: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('reports.quality.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("reports.quality.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("reports.quality.subtitle") }}
                </p>
            </div>
            <DataTable
                :value="report.rows"
                data-key="production_order"
                class="rounded border border-slate-200 bg-white"
            >
                <Column
                    field="production_order"
                    :header="$t('fields.production_order')"
                    sortable
                />
                <Column
                    field="quality_checks"
                    :header="$t('reports.columns.quality_checks')"
                    sortable
                />
                <Column
                    field="accepted"
                    :header="$t('enum.quality_check_result.accepted')"
                    sortable
                />
                <Column
                    field="rejected"
                    :header="$t('enum.quality_check_result.rejected')"
                    sortable
                />
                <Column
                    field="rework"
                    :header="$t('enum.quality_check_result.rework_required')"
                    sortable
                />
                <Column
                    field="acceptance_rate"
                    :header="$t('reports.columns.acceptance_rate')"
                    sortable
                >
                    <template #body="{ data }"
                        ><ProgressBar :value="Number(data.acceptance_rate)"
                    /></template>
                </Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
