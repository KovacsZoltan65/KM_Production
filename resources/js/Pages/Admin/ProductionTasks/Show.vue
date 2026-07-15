<script setup>
import MaterialUsageForm from "@/Components/MaterialUsageForm.vue";
import ProductionTaskActions from "@/Components/ProductionTaskActions.vue";
import ProductionTaskStatusBadge from "@/Components/ProductionTaskStatusBadge.vue";
import QualityCheckForm from "@/Components/QualityCheckForm.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, Link, usePage } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { onMounted, watch } from "vue";

/** @typedef {{id: number, label: string}} EntityOption */
/** @typedef {{id: number, unit: string, label: string}} ItemOption */
/** @typedef {{label: string, value: string}} QualityResultOption */
/**
 * Gyártási feladathoz felhasznált anyag.
 * @typedef {Object} TaskMaterial
 * @property {number} id Az anyagfelhasználás azonosítója.
 * @property {number|string} planned_quantity A tervezett mennyiség.
 * @property {number|string} used_quantity A felhasznált mennyiség.
 * @property {string} unit A mértékegység.
 * @property {string|null} notes A megjegyzés.
 * @property {{item_number: string, name: string}|null} item A felhasznált cikk.
 */
/**
 * Gyártási feladat minőségellenőrzése.
 * @typedef {Object} TaskQualityCheck
 * @property {number} id Az ellenőrzés azonosítója.
 * @property {string} result Az ellenőrzés eredménye.
 * @property {string|null} checked_at Az ellenőrzés időpontja.
 * @property {string|null} notes A megjegyzés.
 * @property {{name: string}|null} inspector Az ellenőrzést végző alkalmazott.
 */
/**
 * Megjelenített gyártási feladat.
 * @typedef {Object} ProductionTaskRecord
 * @property {number} id A feladat azonosítója.
 * @property {string} status A feladat állapota.
 * @property {string|null} started_at A kezdés időpontja.
 * @property {string|null} finished_at A befejezés időpontja.
 * @property {{serial_number: string}|null} item_instance A gyártott példány.
 * @property {{order_number: string, item: {name: string}|null}|null} production_order A gyártási rendelés.
 * @property {{step_order: number, operation_type: {name: string}|null, factory_unit: {code: string}|null}|null} operation_sequence_step A végrehajtandó műveleti lépés.
 * @property {{name: string, employee_number: string}|null} employee A hozzárendelt alkalmazott.
 * @property {TaskMaterial[]} materials A felhasznált anyagok.
 * @property {TaskQualityCheck[]} quality_checks A minőségellenőrzések.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {ProductionTaskRecord} productionTask A megjelenített gyártási feladat.
 * @property {EntityOption[]} employeeOptions A választható munkatársak.
 * @property {ItemOption[]} itemOptions A választható cikkek.
 * @property {EntityOption[]} locationOptions A választható raktárhelyek.
 * @property {QualityResultOption[]} qualityResultOptions A választható ellenőrzési eredmények.
 */
/** @type {Props} */
const props = defineProps({
    productionTask: Object,
    employeeOptions: Array,
    itemOptions: Array,
    locationOptions: Array,
    qualityResultOptions: Array,
});

const page = usePage();
const toast = useToast();
const number = (value) => Number(value || 0).toFixed(3);
const flash = (message) =>
    message && toast.add({ severity: "success", summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head
        :title="
            productionTask.item_instance?.serial_number ||
            $t('production.tasks.task_fallback', { id: productionTask.id })
        "
    />
    <AdminLayout>
        <Toast />
        <div class="space-y-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-2">
                    <Link
                        :href="route('admin.production-tasks.index')"
                        class="text-sm text-blue-700 hover:underline"
                        >{{ $t("production.tasks.back") }}</Link
                    >
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">
                            {{
                                productionTask.item_instance?.serial_number ||
                                $t("production.tasks.task_fallback", {
                                    id: productionTask.id,
                                })
                            }}
                        </h1>
                        <ProductionTaskStatusBadge
                            :status="productionTask.status"
                        />
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ productionTask.production_order?.order_number }} -
                        {{ productionTask.production_order?.item?.name }}
                    </p>
                </div>
                <ProductionTaskActions :task="productionTask" />
            </div>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs uppercase text-slate-500">
                        {{ $t("fields.operation") }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{
                            productionTask.operation_sequence_step?.step_order
                        }}.
                        {{
                            productionTask.operation_sequence_step
                                ?.operation_type?.name
                        }}
                    </div>
                    <div class="mt-1 text-sm text-slate-600">
                        {{
                            productionTask.operation_sequence_step?.factory_unit
                                ?.code
                        }}
                    </div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs uppercase text-slate-500">
                        {{ $t("fields.employee") }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{ productionTask.employee?.name }}
                    </div>
                    <div class="mt-1 text-sm text-slate-600">
                        {{ productionTask.employee?.employee_number }}
                    </div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs uppercase text-slate-500">
                        {{ $t("fields.timing") }}
                    </div>
                    <div class="mt-1 text-sm">
                        {{ $t("fields.started") }}:
                        {{ productionTask.started_at || "-" }}
                    </div>
                    <div class="mt-1 text-sm">
                        {{ $t("fields.finished") }}:
                        {{ productionTask.finished_at || "-" }}
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("production.tasks.materials") }}
                </h2>
                <MaterialUsageForm
                    :production-task="productionTask"
                    :item-options="itemOptions"
                    :location-options="locationOptions"
                />
                <DataTable
                    :value="productionTask.materials"
                    data-key="id"
                    class="rounded border border-slate-200 bg-white"
                >
                    <Column :header="$t('fields.item')"
                        ><template #body="{ data }"
                            >{{ data.item?.item_number }} -
                            {{ data.item?.name }}</template
                        ></Column
                    >
                    <Column :header="$t('fields.planned_quantity')"
                        ><template #body="{ data }"
                            >{{ number(data.planned_quantity) }}
                            {{ data.unit }}</template
                        ></Column
                    >
                    <Column :header="$t('fields.used_quantity')"
                        ><template #body="{ data }"
                            >{{ number(data.used_quantity) }}
                            {{ data.unit }}</template
                        ></Column
                    >
                    <Column field="notes" :header="$t('fields.notes')" />
                </DataTable>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("production.tasks.quality_checks") }}
                </h2>
                <QualityCheckForm
                    v-if="productionTask.status === 'waiting_for_check'"
                    :production-task="productionTask"
                    :employee-options="employeeOptions"
                    :quality-result-options="qualityResultOptions"
                />
                <DataTable
                    :value="productionTask.quality_checks"
                    data-key="id"
                    class="rounded border border-slate-200 bg-white"
                >
                    <Column :header="$t('fields.inspector')"
                        ><template #body="{ data }">{{
                            data.inspector?.name
                        }}</template></Column
                    >
                    <Column field="result" :header="$t('fields.result')">
                        <template #body="{ data }">{{
                            $t(`enum.quality_check_result.${data.result}`)
                        }}</template>
                    </Column>
                    <Column
                        field="checked_at"
                        :header="$t('fields.checked_at')"
                    />
                    <Column field="notes" :header="$t('fields.notes')" />
                </DataTable>
            </section>
        </div>
    </AdminLayout>
</template>
