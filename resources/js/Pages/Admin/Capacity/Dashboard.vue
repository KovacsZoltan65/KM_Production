<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CapacityGauge from "@/Components/CapacityGauge.vue";
import EmployeeLoadTable from "@/Components/EmployeeLoadTable.vue";
import FactoryLoadTable from "@/Components/FactoryLoadTable.vue";

defineOptions({ layout: AdminLayout });

/**
 * Gyártóegység kapacitásterhelése.
 * @typedef {Object} FactoryLoad
 * @property {number} id A gyártóegység azonosítója.
 * @property {string} factory_unit A gyártóegység neve.
 * @property {string} code A gyártóegység kódja.
 * @property {number} available_minutes A rendelkezésre álló idő percben.
 * @property {number} reserved_minutes A lefoglalt idő percben.
 * @property {number} utilization A kihasználtság százalékban.
 * @property {number} current_queue A várakozó feladatok száma.
 * @property {string} status A terhelési állapot.
 */

/**
 * Munkatársi kapacitásterhelés.
 * @typedef {Object} EmployeeLoad
 * @property {number} id A munkatárs azonosítója.
 * @property {string} employee A munkatárs neve.
 * @property {string} professional_role A szakmai szerepkör neve.
 * @property {number} working_minutes A munkaidő percben.
 * @property {number} reserved_minutes A lefoglalt idő percben.
 * @property {number} utilization A kihasználtság százalékban.
 * @property {number} assigned_tasks A kiosztott feladatok száma.
 * @property {string} status A terhelési állapot.
 */

/**
 * Határidőkockázatos vevői rendelés.
 * @typedef {Object} AtRiskOrder
 * @property {number} id A rendelés azonosítója.
 * @property {string} order_number A rendelési szám.
 * @property {string} requested_delivery_date A kért szállítási dátum.
 * @property {string} estimated_finish A becsült befejezés.
 * @property {number} late_by_minutes A várható késés percben.
 * @property {string} critical_factory_unit A kritikus gyártóegység.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ current_factory_load: number, employee_load: number, overloaded_factory_units: number, available_capacity: number, delayed_production_orders: number, average_utilization: number, average_lead_time: number }} summary A kapacitási összesítő mutatói.
 * @property {FactoryLoad[]} top_overloaded_factory_units A legterheltebb gyártóegységek.
 * @property {EmployeeLoad[]} top_busiest_employees A legterheltebb munkatársak.
 * @property {AtRiskOrder[]} orders_likely_to_miss_deadline A várhatóan késő rendelések.
 * @property {FactoryLoad[]} factory_loads A gyártóegységek terhelése.
 * @property {EmployeeLoad[]} employee_loads A munkatársak terhelése.
 */
/** @type {Props} */
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
            <h1 class="text-2xl font-semibold">
                {{ $t("capacity.dashboard.title") }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ $t("capacity.dashboard.subtitle") }}
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <CapacityGauge
                :label="$t('capacity.kpi.average_utilization')"
                :value="summary.average_utilization"
            />
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">
                    {{ $t("capacity.kpi.current_factory_load") }}
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ summary.current_factory_load }} min
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">
                    {{ $t("capacity.kpi.employee_load") }}
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ summary.employee_load }} min
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">
                    {{ $t("capacity.kpi.available_capacity") }}
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ summary.available_capacity }} min
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">
                    {{ $t("capacity.kpi.delayed_orders") }}
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ summary.delayed_production_orders }}
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-500">
                    {{ $t("capacity.kpi.average_lead_time") }}
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ summary.average_lead_time }} min
                </div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("capacity.sections.top_overloaded_factory_units") }}
                </h2>
                <FactoryLoadTable
                    :loads="
                        top_overloaded_factory_units.length
                            ? top_overloaded_factory_units
                            : factory_loads
                    "
                />
            </section>
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("capacity.sections.top_busiest_employees") }}
                </h2>
                <EmployeeLoadTable
                    :loads="
                        top_busiest_employees.length
                            ? top_busiest_employees
                            : employee_loads
                    "
                />
            </section>
        </div>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold">
                {{ $t("capacity.sections.orders_likely_to_miss_deadline") }}
            </h2>
            <div
                class="overflow-x-auto rounded border border-slate-200 bg-white"
            >
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead
                        class="bg-slate-50 text-left text-xs uppercase text-slate-500"
                    >
                        <tr>
                            <th class="px-3 py-2">{{ $t("fields.order") }}</th>
                            <th class="px-3 py-2">
                                {{ $t("orders.fields.requested_delivery") }}
                            </th>
                            <th class="px-3 py-2">
                                {{ $t("capacity.fields.estimated_finish") }}
                            </th>
                            <th class="px-3 py-2">
                                {{ $t("capacity.fields.late_by") }}
                            </th>
                            <th class="px-3 py-2">
                                {{
                                    $t("capacity.fields.critical_factory_unit")
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="order in orders_likely_to_miss_deadline"
                            :key="order.id"
                        >
                            <td class="px-3 py-2 font-medium">
                                {{ order.order_number }}
                            </td>
                            <td class="px-3 py-2">
                                {{ order.requested_delivery_date }}
                            </td>
                            <td class="px-3 py-2">
                                {{ order.estimated_finish }}
                            </td>
                            <td class="px-3 py-2">
                                {{ order.late_by_minutes }} min
                            </td>
                            <td class="px-3 py-2">
                                {{ order.critical_factory_unit }}
                            </td>
                        </tr>
                        <tr v-if="!orders_likely_to_miss_deadline.length">
                            <td class="px-3 py-4 text-slate-500" colspan="5">
                                {{ $t("capacity.empty.no_deadline_misses") }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</template>
