<script setup>
import BottleneckTable from "@/Components/BottleneckTable.vue";
import IntelligenceMetricCard from "@/Components/IntelligenceMetricCard.vue";
import MaterialRiskTable from "@/Components/MaterialRiskTable.vue";
import RecommendationCard from "@/Components/RecommendationCard.vue";
import RiskBadge from "@/Components/RiskBadge.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

defineProps({ dashboard: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.dashboard.title')" />
    <AdminLayout>
        <div class="space-y-5">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.dashboard.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.dashboard.subtitle") }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <IntelligenceMetricCard
                    :title="$t('intelligence.kpi.high_risk_orders')"
                    :value="dashboard.high_risk_orders.length"
                />
                <IntelligenceMetricCard
                    :title="$t('intelligence.kpi.materials_at_risk')"
                    :value="dashboard.materials_at_risk.length"
                />
                <IntelligenceMetricCard
                    :title="$t('intelligence.kpi.recommended_purchases')"
                    :value="dashboard.recommended_purchases.length"
                />
            </div>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("intelligence.sections.high_risk_orders") }}
                </h2>
                <DataTable
                    :value="dashboard.high_risk_orders"
                    class="rounded border border-slate-200 bg-white"
                >
                    <Column field="customer_order" :header="$t('fields.order')" />
                    <Column field="customer" :header="$t('fields.customer')" />
                    <Column
                        field="days_until_requested_delivery"
                        :header="$t('intelligence.columns.days_left')"
                    />
                    <Column field="risk_score" :header="$t('fields.score')" />
                    <Column field="risk_level" :header="$t('fields.risk')"
                        ><template #body="{ data }"
                            ><RiskBadge :value="data.risk_level" /></template
                    ></Column>
                </DataTable>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("intelligence.sections.bottleneck_factory_units") }}
                </h2>
                <BottleneckTable :rows="dashboard.bottleneck_factory_units" />
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("intelligence.sections.materials_at_risk") }}
                </h2>
                <MaterialRiskTable :rows="dashboard.materials_at_risk" />
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold">
                        {{ $t("intelligence.sections.slow_suppliers") }}
                    </h2>
                    <DataTable
                        :value="dashboard.slow_suppliers"
                        class="rounded border border-slate-200 bg-white"
                    >
                        <Column field="supplier" :header="$t('fields.supplier')" />
                        <Column
                            field="average_delivery_days"
                            :header="$t('intelligence.columns.avg_days')"
                        />
                        <Column
                            field="late_delivery_count"
                            :header="$t('intelligence.columns.late')"
                        />
                        <Column
                            field="on_time_rate"
                            :header="$t('intelligence.columns.on_time_percent')"
                        />
                    </DataTable>
                </div>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold">
                        {{ $t("intelligence.sections.quality_risk_products") }}
                    </h2>
                    <DataTable
                        :value="dashboard.quality_risk_products"
                        class="rounded border border-slate-200 bg-white"
                    >
                        <Column field="item" :header="$t('fields.item')" />
                        <Column field="production_order" :header="$t('fields.order')" />
                        <Column
                            field="defect_rate"
                            :header="$t('intelligence.columns.defect_percent')"
                        />
                    </DataTable>
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("intelligence.sections.recommended_purchases") }}
                </h2>
                <div class="grid gap-4 lg:grid-cols-2">
                    <RecommendationCard
                        v-for="item in dashboard.recommended_purchases"
                        :key="item.item_id"
                        :recommendation="item"
                    />
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
