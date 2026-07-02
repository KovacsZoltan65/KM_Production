<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

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
                <Column field="supplier" :header="$t('fields.supplier')" sortable />
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
