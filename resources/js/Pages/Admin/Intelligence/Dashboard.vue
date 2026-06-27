<script setup>
import BottleneckTable from '@/Components/BottleneckTable.vue';
import IntelligenceMetricCard from '@/Components/IntelligenceMetricCard.vue';
import MaterialRiskTable from '@/Components/MaterialRiskTable.vue';
import RecommendationCard from '@/Components/RecommendationCard.vue';
import RiskBadge from '@/Components/RiskBadge.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

defineProps({ dashboard: { type: Object, required: true } });
</script>

<template>
    <Head title="Manufacturing Intelligence" />
    <AdminLayout>
        <div class="space-y-5">
            <div>
                <h1 class="text-2xl font-semibold">Manufacturing Intelligence</h1>
                <p class="mt-1 text-sm text-slate-600">Readonly production, stock, procurement, and quality signals.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <IntelligenceMetricCard title="High Risk Orders" :value="dashboard.high_risk_orders.length" />
                <IntelligenceMetricCard title="Materials At Risk" :value="dashboard.materials_at_risk.length" />
                <IntelligenceMetricCard title="Recommended Purchases" :value="dashboard.recommended_purchases.length" />
            </div>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">High Risk Orders</h2>
                <DataTable :value="dashboard.high_risk_orders" class="rounded border border-slate-200 bg-white">
                    <Column field="customer_order" header="Order" />
                    <Column field="customer" header="Customer" />
                    <Column field="days_until_requested_delivery" header="Days Left" />
                    <Column field="risk_score" header="Score" />
                    <Column field="risk_level" header="Risk"><template #body="{ data }"><RiskBadge :value="data.risk_level" /></template></Column>
                </DataTable>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Bottleneck Factory Units</h2>
                <BottleneckTable :rows="dashboard.bottleneck_factory_units" />
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Materials At Risk</h2>
                <MaterialRiskTable :rows="dashboard.materials_at_risk" />
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold">Slow Suppliers</h2>
                    <DataTable :value="dashboard.slow_suppliers" class="rounded border border-slate-200 bg-white">
                        <Column field="supplier" header="Supplier" />
                        <Column field="average_delivery_days" header="Avg Days" />
                        <Column field="late_delivery_count" header="Late" />
                        <Column field="on_time_rate" header="On Time %" />
                    </DataTable>
                </div>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold">Quality Risk Products</h2>
                    <DataTable :value="dashboard.quality_risk_products" class="rounded border border-slate-200 bg-white">
                        <Column field="item" header="Item" />
                        <Column field="production_order" header="Order" />
                        <Column field="defect_rate" header="Defect %" />
                    </DataTable>
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Recommended Purchases</h2>
                <div class="grid gap-4 lg:grid-cols-2">
                    <RecommendationCard v-for="item in dashboard.recommended_purchases" :key="item.item_id" :recommendation="item" />
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
