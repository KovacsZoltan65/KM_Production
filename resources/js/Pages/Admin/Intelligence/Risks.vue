<script setup>
import RiskBadge from "@/Components/RiskBadge.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

defineProps({ risks: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.risks.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.risks.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.risks.subtitle") }}
                </p>
            </div>
            <DataTable
                :value="risks.rows"
                class="rounded border border-slate-200 bg-white"
            >
                <Column field="customer_order" :header="$t('fields.order')" sortable />
                <Column field="customer" :header="$t('fields.customer')" sortable />
                <Column
                    field="requested_delivery_date"
                    :header="$t('reports.columns.requested_delivery')"
                    sortable
                />
                <Column
                    field="material_shortage"
                    :header="$t('fields.shortage')"
                    sortable
                />
                <Column
                    field="capacity_delay"
                    :header="$t('fields.capacity_delay')"
                    sortable
                />
                <Column
                    field="quality_rejects"
                    :header="$t('navigation.quality_report')"
                    sortable
                />
                <Column
                    field="supplier_delay"
                    :header="$t('fields.supplier_delay')"
                    sortable
                />
                <Column field="risk_score" :header="$t('fields.score')" sortable />
                <Column field="risk_level" :header="$t('fields.risk')" sortable
                    ><template #body="{ data }"
                        ><RiskBadge :value="data.risk_level" /></template
                ></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
