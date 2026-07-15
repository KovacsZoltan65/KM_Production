<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, Link } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

/**
 * Hiányzó anyag összesített sora.
 * @typedef {Object} MissingMaterial
 * @property {number} item_id A cikk azonosítója.
 * @property {string} item_number A cikkszám.
 * @property {string} name A cikk megnevezése.
 * @property {number} missing_quantity A hiányzó mennyiség.
 * @property {string} unit A mértékegység.
 */
/**
 * A beszerzési irányítópult mutatói.
 * @typedef {Object} ProcurementMetrics
 * @property {number} open_purchase_orders A nyitott beszerzési rendelések száma.
 * @property {number} open_requisitions A nyitott beszerzési igények száma.
 * @property {number} pending_goods_receipts A függő áruátvételek száma.
 * @property {number} shortages_count A hiányok száma.
 * @property {MissingMaterial[]} top_missing_materials A legnagyobb anyaghiányok.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {ProcurementMetrics} metrics A beszerzési irányítópult mutatói.
 */
/** @type {Props} */
defineProps({ metrics: Object });

const number = (value) => Number(value || 0).toFixed(3);
</script>

<template>
    <Head :title="$t('procurement.dashboard.title')" />

    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("procurement.dashboard.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("procurement.dashboard.subtitle") }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <Link
                    :href="route('admin.purchase-requisitions.index')"
                    class="rounded border border-slate-200 bg-white p-4 hover:border-blue-300"
                >
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ $t("procurement.dashboard.kpi.open_requisitions") }}
                    </div>
                    <div class="mt-2 text-3xl font-semibold">
                        {{ metrics.open_requisitions }}
                    </div>
                </Link>
                <Link
                    :href="route('admin.purchase-orders.index')"
                    class="rounded border border-slate-200 bg-white p-4 hover:border-blue-300"
                >
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ $t("admin.dashboard.kpi.open_purchase_orders") }}
                    </div>
                    <div class="mt-2 text-3xl font-semibold">
                        {{ metrics.open_purchase_orders }}
                    </div>
                </Link>
                <Link
                    :href="route('admin.goods-receipts.index')"
                    class="rounded border border-slate-200 bg-white p-4 hover:border-blue-300"
                >
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ $t("admin.dashboard.kpi.pending_goods_receipts") }}
                    </div>
                    <div class="mt-2 text-3xl font-semibold">
                        {{ metrics.pending_goods_receipts }}
                    </div>
                </Link>
                <Link
                    :href="route('admin.inventory.shortages.index')"
                    class="rounded border border-slate-200 bg-white p-4 hover:border-blue-300"
                >
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ $t("procurement.dashboard.kpi.shortages_count") }}
                    </div>
                    <div class="mt-2 text-3xl font-semibold">
                        {{ metrics.shortages_count }}
                    </div>
                </Link>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">
                    {{
                        $t(
                            "procurement.dashboard.sections.top_missing_materials",
                        )
                    }}
                </h2>
                <DataTable
                    :value="metrics.top_missing_materials"
                    data-key="item_id"
                >
                    <Column :header="$t('fields.item')">
                        <template #body="{ data }"
                            >{{ data.item_number }} - {{ data.name }}</template
                        >
                    </Column>
                    <Column :header="$t('fields.missing')">
                        <template #body="{ data }"
                            >{{ number(data.missing_quantity) }}
                            {{ data.unit }}</template
                        >
                    </Column>
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
