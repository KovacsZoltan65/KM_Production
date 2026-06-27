<script setup>
import DashboardChartCard from '@/Components/DashboardChartCard.vue';
import DashboardStatCard from '@/Components/DashboardStatCard.vue';
import StatusDonutChart from '@/Components/StatusDonutChart.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    summary: { type: Object, required: true },
});

const kpiCards = [
    ['Open Customer Orders', 'open_customer_orders', 'pi pi-shopping-cart', 'blue'],
    ['Active Production Plans', 'active_production_plans', 'pi pi-calendar-clock', 'green'],
    ['Open Production Orders', 'open_production_orders', 'pi pi-list-check', 'blue'],
    ['Ready Production Tasks', 'ready_production_tasks', 'pi pi-play-circle', 'green'],
    ['In Progress Tasks', 'in_progress_tasks', 'pi pi-spin pi-spinner', 'amber'],
    ['Waiting For QC', 'waiting_for_qc', 'pi pi-check-circle', 'amber'],
    ['Open Purchase Orders', 'open_purchase_orders', 'pi pi-shopping-bag', 'blue'],
    ['Pending Goods Receipts', 'pending_goods_receipts', 'pi pi-inbox', 'amber'],
    ['Shortages', 'shortages', 'pi pi-exclamation-triangle', 'rose'],
    ['Current Stock Value', 'current_stock_value', 'pi pi-warehouse', 'slate'],
    ['Documents Waiting Approval', 'documents_waiting_approval', 'pi pi-file-check', 'amber'],
];
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Dashboard</h1>
                <p class="mt-1 text-sm text-slate-600">Daily operational view across orders, production, inventory, procurement, quality, and documents.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <DashboardStatCard
                    v-for="[label, key, icon, tone] in kpiCards"
                    :key="key"
                    :label="label"
                    :value="props.summary.kpis[key] ?? 0"
                    :icon="icon"
                    :tone="tone"
                />
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <DashboardChartCard title="Orders by Status">
                    <StatusDonutChart :rows="props.summary.charts.customer_orders_by_status" />
                </DashboardChartCard>
                <DashboardChartCard title="Production Orders by Status">
                    <StatusDonutChart :rows="props.summary.charts.production_orders_by_status" />
                </DashboardChartCard>
                <DashboardChartCard title="Production Tasks by Status">
                    <StatusDonutChart :rows="props.summary.charts.production_tasks_by_status" />
                </DashboardChartCard>
                <DashboardChartCard title="Purchase Orders by Status">
                    <StatusDonutChart :rows="props.summary.charts.purchase_orders_by_status" />
                </DashboardChartCard>
                <DashboardChartCard title="Quality Check Results">
                    <StatusDonutChart :rows="props.summary.charts.quality_check_results" />
                </DashboardChartCard>
            </div>
        </div>
    </AdminLayout>
</template>
