<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import EmployeeLoadTable from "@/Components/EmployeeLoadTable.vue";

defineOptions({ layout: AdminLayout });

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
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {EmployeeLoad[]} loads A munkatársak kapacitásterhelése.
 */
/** @type {Props} */
defineProps({
    loads: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="space-y-5">
        <div>
            <h1 class="text-2xl font-semibold">
                {{ $t("capacity.employees.title") }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ $t("capacity.employees.subtitle") }}
            </p>
        </div>
        <EmployeeLoadTable :loads="loads" />
    </section>
</template>
