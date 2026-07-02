<script setup>
import DashboardChartCard from "@/Components/DashboardChartCard.vue";
import DashboardStatCard from "@/Components/DashboardStatCard.vue";
import StatusDonutChart from "@/Components/StatusDonutChart.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

import { trans } from "laravel-vue-i18n";

const props = defineProps({
    summary: { type: Object, required: true },
});

const kpiCards = [
    ["admin.dashboard.kpi.open_customer_orders", "open_customer_orders", "pi pi-shopping-cart", "blue"],
    [
        "admin.dashboard.kpi.active_production_plans",
        "active_production_plans",
        "pi pi-calendar-clock",
        "green",
    ],
    ["admin.dashboard.kpi.open_production_orders", "open_production_orders", "pi pi-list-check", "blue"],
    ["admin.dashboard.kpi.ready_production_tasks", "ready_production_tasks", "pi pi-play-circle", "green"],
    ["admin.dashboard.kpi.in_progress_tasks", "in_progress_tasks", "pi pi-spin pi-spinner", "amber"],
    ["admin.dashboard.kpi.waiting_for_qc", "waiting_for_qc", "pi pi-check-circle", "amber"],
    ["admin.dashboard.kpi.open_purchase_orders", "open_purchase_orders", "pi pi-shopping-bag", "blue"],
    ["admin.dashboard.kpi.pending_goods_receipts", "pending_goods_receipts", "pi pi-inbox", "amber"],
    ["admin.dashboard.kpi.shortages", "shortages", "pi pi-exclamation-triangle", "rose"],
    ["admin.dashboard.kpi.current_stock_value", "current_stock_value", "pi pi-warehouse", "slate"],
    [
        "admin.dashboard.kpi.documents_waiting_approval",
        "documents_waiting_approval",
        "pi pi-file-check",
        "amber",
    ],
];
</script>

<template>
    <Head :title="$t('admin.dashboard.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">{{ $t("admin.dashboard.title") }}</h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("admin.dashboard.subtitle") }}
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <DashboardStatCard
                    v-for="[labelKey, key, icon, tone] in kpiCards"
                    :key="key"
                    :label="trans(labelKey)"
                    :value="props.summary.kpis[key] ?? 0"
                    :icon="icon"
                    :tone="tone"
                />
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <DashboardChartCard :title="trans('admin.dashboard.charts.orders_by_status')">
                    <StatusDonutChart
                        :rows="props.summary.charts.customer_orders_by_status"
                    />
                </DashboardChartCard>
                <DashboardChartCard :title="trans('admin.dashboard.charts.production_orders_by_status')">
                    <StatusDonutChart
                        :rows="props.summary.charts.production_orders_by_status"
                    />
                </DashboardChartCard>
                <DashboardChartCard :title="trans('admin.dashboard.charts.production_tasks_by_status')">
                    <StatusDonutChart
                        :rows="props.summary.charts.production_tasks_by_status"
                    />
                </DashboardChartCard>
                <DashboardChartCard :title="trans('admin.dashboard.charts.purchase_orders_by_status')">
                    <StatusDonutChart
                        :rows="props.summary.charts.purchase_orders_by_status"
                    />
                </DashboardChartCard>
                <DashboardChartCard :title="trans('admin.dashboard.charts.quality_check_results')">
                    <StatusDonutChart
                        :rows="props.summary.charts.quality_check_results"
                    />
                </DashboardChartCard>
            </div>
        </div>
    </AdminLayout>
</template>
