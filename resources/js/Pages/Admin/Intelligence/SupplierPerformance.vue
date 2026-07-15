<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

/**
 * Beszállítói teljesítménysor.
 * @typedef {Object} SupplierPerformanceRow
 * @property {string} supplier A beszállító neve.
 * @property {number} purchase_orders_count A beszerzési rendelések száma.
 * @property {number} goods_receipts_count Az áruátvételek száma.
 * @property {number|null} average_delivery_days Az átlagos szállítási idő napban.
 * @property {number} late_delivery_count A késő szállítások száma.
 * @property {number|null} on_time_rate A határidőre szállítás aránya.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ rows: SupplierPerformanceRow[] }} performance A beszállítói teljesítményelemzés.
 */
/** @type {Props} */
defineProps({ performance: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.supplier_performance.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.supplier_performance.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.supplier_performance.subtitle") }}
                </p>
            </div>
            <DataTable
                :value="performance.rows"
                class="rounded border border-slate-200 bg-white"
            >
                <Column
                    field="supplier"
                    :header="$t('fields.supplier')"
                    sortable
                />
                <Column
                    field="purchase_orders_count"
                    :header="$t('navigation.purchase_orders')"
                    sortable
                />
                <Column
                    field="goods_receipts_count"
                    :header="$t('fields.receipts')"
                    sortable
                />
                <Column
                    field="average_delivery_days"
                    :header="$t('intelligence.columns.avg_delivery_days')"
                    sortable
                />
                <Column
                    field="late_delivery_count"
                    :header="$t('intelligence.columns.late_deliveries')"
                    sortable
                />
                <Column
                    field="on_time_rate"
                    :header="$t('intelligence.columns.on_time_percent')"
                    sortable
                />
            </DataTable>
        </div>
    </AdminLayout>
</template>
