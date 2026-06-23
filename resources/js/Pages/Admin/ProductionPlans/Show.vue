<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProductionPlanStatusBadge from '@/Pages/Admin/ProductionPlans/Partials/ProductionPlanStatusBadge.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, watch } from 'vue';

const props = defineProps({
    productionPlan: Object,
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

const dateValue = (value) => (value ? String(value).slice(0, 10) : '-');
const canApprove = computed(() => ['draft', 'calculated'].includes(props.productionPlan.status));
const productionOrders = computed(() => props.productionPlan.items.flatMap((item) => item.production_orders || []));

const approvePlan = () => {
    confirm.require({
        message: `Approve ${props.productionPlan.plan_number}?`,
        header: 'Approve production plan',
        icon: 'pi pi-check-circle',
        accept: () => router.patch(`/admin/production-plans/${props.productionPlan.id}/approve`, {}, { preserveScroll: true }),
    });
};

const generateProductionOrders = () => {
    confirm.require({
        message: `Generate production orders for ${props.productionPlan.plan_number}?`,
        header: 'Generate production orders',
        icon: 'pi pi-cog',
        accept: () => router.post(`/admin/production-plans/${props.productionPlan.id}/generate-production-orders`, {}, { preserveScroll: true }),
    });
};

onMounted(() => {
    if (page.props.flash?.success) {
        toast.add({ severity: 'success', summary: page.props.flash.success, life: 2500 });
    }
});

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({ severity: 'success', summary: message, life: 2500 });
        }
    },
);
</script>

<template>
    <Head :title="productionPlan.plan_number" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link href="/admin/production-plans" class="text-sm text-blue-700 hover:underline">Back to production plans</Link>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">{{ productionPlan.plan_number }}</h1>
                        <ProductionPlanStatusBadge :status="productionPlan.status" />
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ productionPlan.customer_order?.order_number }} - {{ productionPlan.customer_order?.customer?.name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button v-if="canApprove" type="button" label="Approve" icon="pi pi-check" severity="success" @click="approvePlan" />
                    <Button type="button" label="Generate Production Orders" icon="pi pi-cog" outlined @click="generateProductionOrders" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">Customer order</div>
                    <div class="mt-1 font-medium">{{ productionPlan.customer_order?.order_number }}</div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">Planned start</div>
                    <div class="mt-1 font-medium">{{ dateValue(productionPlan.planned_start_date) }}</div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">Planned finish</div>
                    <div class="mt-1 font-medium">{{ dateValue(productionPlan.planned_finish_date) }}</div>
                </div>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">Plan items</h2>
                <DataTable :value="productionPlan.items" data-key="id">
                    <Column header="Item">
                        <template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template>
                    </Column>
                    <Column field="quantity" header="Quantity" />
                    <Column header="BOM">
                        <template #body="{ data }">{{ data.bom ? `V${data.bom.version} - ${data.bom.name}` : '-' }}</template>
                    </Column>
                    <Column header="Operation Sequence">
                        <template #body="{ data }">{{ data.operation_sequence ? `V${data.operation_sequence.version} - ${data.operation_sequence.name}` : '-' }}</template>
                    </Column>
                    <Column field="status" header="Status">
                        <template #body="{ data }"><ProductionPlanStatusBadge :status="data.status" /></template>
                    </Column>
                </DataTable>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">Production Orders</h2>
                <DataTable :value="productionOrders" data-key="id">
                    <Column field="order_number" header="Order number" />
                    <Column header="Item">
                        <template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template>
                    </Column>
                    <Column field="status" header="Status">
                        <template #body="{ data }"><ProductionPlanStatusBadge :status="data.status" /></template>
                    </Column>
                    <Column field="quantity" header="Quantity" />
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
