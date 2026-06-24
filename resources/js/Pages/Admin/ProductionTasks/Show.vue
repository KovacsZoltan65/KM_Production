<script setup>
import MaterialUsageForm from '@/Components/MaterialUsageForm.vue';
import ProductionTaskActions from '@/Components/ProductionTaskActions.vue';
import ProductionTaskStatusBadge from '@/Components/ProductionTaskStatusBadge.vue';
import QualityCheckForm from '@/Components/QualityCheckForm.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { onMounted, watch } from 'vue';

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
const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head :title="productionTask.item_instance?.serial_number || `Task #${productionTask.id}`" />
    <AdminLayout>
        <Toast />
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link :href="route('admin.production-tasks.index')" class="text-sm text-blue-700 hover:underline">Back to production tasks</Link>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">{{ productionTask.item_instance?.serial_number || `Task #${productionTask.id}` }}</h1>
                        <ProductionTaskStatusBadge :status="productionTask.status" />
                    </div>
                    <p class="text-sm text-slate-600">{{ productionTask.production_order?.order_number }} - {{ productionTask.production_order?.item?.name }}</p>
                </div>
                <ProductionTaskActions :task="productionTask" />
            </div>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs uppercase text-slate-500">Operation</div>
                    <div class="mt-1 font-medium">{{ productionTask.operation_sequence_step?.step_order }}. {{ productionTask.operation_sequence_step?.operation_type?.name }}</div>
                    <div class="mt-1 text-sm text-slate-600">{{ productionTask.operation_sequence_step?.factory_unit?.code }}</div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs uppercase text-slate-500">Employee</div>
                    <div class="mt-1 font-medium">{{ productionTask.employee?.name }}</div>
                    <div class="mt-1 text-sm text-slate-600">{{ productionTask.employee?.employee_number }}</div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs uppercase text-slate-500">Timing</div>
                    <div class="mt-1 text-sm">Started: {{ productionTask.started_at || '-' }}</div>
                    <div class="mt-1 text-sm">Finished: {{ productionTask.finished_at || '-' }}</div>
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Materials</h2>
                <MaterialUsageForm :production-task="productionTask" :item-options="itemOptions" :location-options="locationOptions" />
                <DataTable :value="productionTask.materials" data-key="id" class="rounded border border-slate-200 bg-white">
                    <Column header="Item"><template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template></Column>
                    <Column header="Planned"><template #body="{ data }">{{ number(data.planned_quantity) }} {{ data.unit }}</template></Column>
                    <Column header="Used"><template #body="{ data }">{{ number(data.used_quantity) }} {{ data.unit }}</template></Column>
                    <Column field="notes" header="Notes" />
                </DataTable>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Quality Checks</h2>
                <QualityCheckForm v-if="productionTask.status === 'waiting_for_check'" :production-task="productionTask" :employee-options="employeeOptions" :quality-result-options="qualityResultOptions" />
                <DataTable :value="productionTask.quality_checks" data-key="id" class="rounded border border-slate-200 bg-white">
                    <Column header="Inspector"><template #body="{ data }">{{ data.inspector?.name }}</template></Column>
                    <Column field="result" header="Result" />
                    <Column field="checked_at" header="Checked at" />
                    <Column field="notes" header="Notes" />
                </DataTable>
            </section>
        </div>
    </AdminLayout>
</template>
