<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

/**
 * Beszerzési riport sora.
 * @typedef {Object} ProcurementReportRow
 * @property {string} supplier A beszállító neve.
 * @property {number} purchase_orders A beszerzési rendelések száma.
 * @property {number} open A nyitott rendelések száma.
 * @property {number} closed A lezárt rendelések száma.
 * @property {number} goods_receipts_pending A függő áruátvételek száma.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ rows: ProcurementReportRow[] }} report A beszerzési riport.
 */
/** @type {Props} */
defineProps({ report: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('reports.procurement.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("reports.procurement.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("reports.procurement.subtitle") }}
                </p>
            </div>
            <DataTable
                :value="report.rows"
                data-key="supplier"
                class="rounded border border-slate-200 bg-white"
            >
                <Column
                    field="supplier"
                    :header="$t('fields.supplier')"
                    sortable
                />
                <Column
                    field="purchase_orders"
                    :header="$t('navigation.purchase_orders')"
                    sortable
                />
                <Column field="open" :header="$t('fields.open')" sortable />
                <Column field="closed" :header="$t('fields.closed')" sortable />
                <Column
                    field="goods_receipts_pending"
                    :header="$t('admin.dashboard.kpi.pending_goods_receipts')"
                    sortable
                />
            </DataTable>
        </div>
    </AdminLayout>
</template>
