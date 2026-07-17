<script setup>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import { loadStatusSeverity } from "@/Utils/dashboard";

/**
 * Munkatársi kapacitásterhelés.
 * @typedef {Object} EmployeeLoad
 * @property {string} employee A munkatárs neve.
 * @property {string} professional_role A szakmai szerepkör neve.
 * @property {number} working_minutes A munkaidő percben.
 * @property {number} reserved_minutes A lefoglalt idő percben.
 * @property {number} utilization A kihasználtság százalékban.
 * @property {'green'|'yellow'|'red'|string} status A terhelési állapot.
 * @property {number} assigned_tasks A kiosztott feladatok száma.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {EmployeeLoad[]} loads A megjelenített munkatársi terhelések.
 */
/** @type {Props} */
defineProps({
    loads: { type: Array, default: () => [] },
});

const severityFor = loadStatusSeverity;
</script>

<template>
    <DataTable
        :value="loads"
        stripedRows
        size="small"
        responsiveLayout="scroll"
    >
        <Column field="employee" header="Employee" />
        <Column field="professional_role" header="Professional Role" />
        <Column field="working_minutes" header="Working Minutes" />
        <Column field="reserved_minutes" header="Reserved Minutes" />
        <Column field="utilization" header="Utilization">
            <template #body="{ data }">
                <Tag
                    :value="`${data.utilization}%`"
                    :severity="severityFor(data.status)"
                />
            </template>
        </Column>
        <Column field="assigned_tasks" header="Assigned Tasks" />
    </DataTable>
</template>
