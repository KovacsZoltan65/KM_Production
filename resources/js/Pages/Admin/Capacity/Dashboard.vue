<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CapacityGauge from '@/Components/CapacityGauge.vue';
import EmployeeLoadTable from '@/Components/EmployeeLoadTable.vue';
import FactoryLoadTable from '@/Components/FactoryLoadTable.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    summary: { type: Object, required: true },
    top_overloaded_factory_units: { type: Array, default: () => [] },
    top_busiest_employees: { type: Array, default: () => [] },
    orders_likely_to_miss_deadline: { type: Array, default: () => [] },
    factory_loads: { type: Array, default: () => [] },
    employee_loads: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="space-y-5">
        <div>
            <h1 class="text-2xl font-semibold">Capacity Dashboard</h1>
            <p class="text-sm text-slate-500">Readonly factory and employee load overview.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <CapacityGauge label="Average Utilization" :value="summary.average_utilization" />
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">Current Factory Load</div>
                <div class="mt-2 text-2xl font-semibold">{{ summary.current_factory_load }} min</div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">Employee Load</div>
                <div class="mt-2 text-2xl font-semibold">{{ summary.employee_load }} min</div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">Available Capacity</div>
                <div class="mt-2 text-2xl font-semibold">{{ summary.available_capacity }} min</div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">Delayed Orders</div>
                <div class="mt-2 text-2xl font-semibold">{{ summary.delayed_production_orders }}</div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">Average Lead Time</div>
                <div class="mt-2 text-2xl font-semibold">{{ summary.average_lead_time }} min</div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Top 5 Overloaded Factory Units</h2>
                <FactoryLoadTable :loads="top_overloaded_factory_units.length ? top_overloaded_factory_units : factory_loads" />
            </section>
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Top 5 Busiest Employees</h2>
                <EmployeeLoadTable :loads="top_busiest_employees.length ? top_busiest_employees : employee_loads" />
            </section>
        </div>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold">Orders Likely To Miss Deadline</h2>
            <div class="overflow-x-auto rounded border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Order</th>
                            <th class="px-3 py-2">Requested Delivery</th>
                            <th class="px-3 py-2">Estimated Finish</th>
                            <th class="px-3 py-2">Late By</th>
                            <th class="px-3 py-2">Critical Factory Unit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="order in orders_likely_to_miss_deadline" :key="order.id">
                            <td class="px-3 py-2 font-medium">{{ order.order_number }}</td>
                            <td class="px-3 py-2">{{ order.requested_delivery_date }}</td>
                            <td class="px-3 py-2">{{ order.estimated_finish }}</td>
                            <td class="px-3 py-2">{{ order.late_by_minutes }} min</td>
                            <td class="px-3 py-2">{{ order.critical_factory_unit }}</td>
                        </tr>
                        <tr v-if="!orders_likely_to_miss_deadline.length">
                            <td class="px-3 py-4 text-slate-500" colspan="5">No likely deadline misses in the current planning window.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</template>
