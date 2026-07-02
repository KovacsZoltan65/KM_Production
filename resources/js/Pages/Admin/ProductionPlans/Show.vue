<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProductionPlanStatusBadge from "@/Pages/Admin/ProductionPlans/Partials/ProductionPlanStatusBadge.vue";
import { route } from "@/Utils/routes";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { computed, onMounted, watch } from "vue";

const props = defineProps({
    productionPlan: Object,
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

const dateValue = (value) => (value ? String(value).slice(0, 10) : "-");
const canApprove = computed(() =>
    ["draft", "calculated"].includes(props.productionPlan.status)
);
const productionOrders = computed(() =>
    props.productionPlan.items.flatMap((item) => item.production_orders || [])
);

const approvePlan = () => {
    confirm.require({
        message: trans("production.plans.confirm_approve_message", {
            number: props.productionPlan.plan_number,
        }),
        header: trans("production.plans.confirm_approve_header"),
        icon: "pi pi-check-circle",
        accept: () =>
            router.patch(
                route("admin.production-plans.approve", props.productionPlan.id),
                {},
                { preserveScroll: true }
            ),
    });
};

const generateProductionOrders = () => {
    confirm.require({
        message: trans("production.plans.confirm_generate_orders_message", {
            number: props.productionPlan.plan_number,
        }),
        header: trans("production.plans.confirm_generate_orders_header"),
        icon: "pi pi-cog",
        accept: () =>
            router.post(
                route(
                    "admin.production-plans.generate-production-orders",
                    props.productionPlan.id
                ),
                {},
                { preserveScroll: true }
            ),
    });
};

onMounted(() => {
    if (page.props.flash?.success) {
        toast.add({ severity: "success", summary: page.props.flash.success, life: 2500 });
    }
});

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({ severity: "success", summary: message, life: 2500 });
        }
    }
);
</script>

<template>
    <Head :title="productionPlan.plan_number" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-2">
                    <Link
                        :href="route('admin.production-plans.index')"
                        class="text-sm text-blue-700 hover:underline"
                        >{{ $t("production.plans.back") }}</Link
                    >
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">
                            {{ productionPlan.plan_number }}
                        </h1>
                        <ProductionPlanStatusBadge :status="productionPlan.status" />
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ productionPlan.customer_order?.order_number }} -
                        {{ productionPlan.customer_order?.customer?.name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="canApprove"
                        type="button"
                        :label="$t('actions.approve')"
                        icon="pi pi-check"
                        severity="success"
                        @click="approvePlan"
                    />
                    <Button
                        type="button"
                        :label="$t('production.plans.generate_orders')"
                        icon="pi pi-cog"
                        outlined
                        @click="generateProductionOrders"
                    />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ $t("fields.customer_order") }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{ productionPlan.customer_order?.order_number }}
                    </div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ $t("fields.planned_start") }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{ dateValue(productionPlan.planned_start_date) }}
                    </div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ $t("fields.planned_finish") }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{ dateValue(productionPlan.planned_finish_date) }}
                    </div>
                </div>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">
                    {{ $t("production.plans.items") }}
                </h2>
                <DataTable :value="productionPlan.items" data-key="id">
                    <Column :header="$t('fields.item')">
                        <template #body="{ data }"
                            >{{ data.item?.item_number }} -
                            {{ data.item?.name }}</template
                        >
                    </Column>
                    <Column field="quantity" :header="$t('fields.quantity')" />
                    <Column :header="$t('fields.bom')">
                        <template #body="{ data }">{{
                            data.bom ? `V${data.bom.version} - ${data.bom.name}` : "-"
                        }}</template>
                    </Column>
                    <Column :header="$t('fields.operation_sequence')">
                        <template #body="{ data }">{{
                            data.operation_sequence
                                ? `V${data.operation_sequence.version} - ${data.operation_sequence.name}`
                                : "-"
                        }}</template>
                    </Column>
                    <Column field="status" :header="$t('fields.status')">
                        <template #body="{ data }"
                            ><ProductionPlanStatusBadge :status="data.status"
                        /></template>
                    </Column>
                </DataTable>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">
                    {{ $t("production.plans.production_orders") }}
                </h2>
                <DataTable :value="productionOrders" data-key="id">
                    <Column
                        field="order_number"
                        :header="$t('reports.columns.order_number')"
                    />
                    <Column :header="$t('fields.item')">
                        <template #body="{ data }"
                            >{{ data.item?.item_number }} -
                            {{ data.item?.name }}</template
                        >
                    </Column>
                    <Column field="status" :header="$t('fields.status')">
                        <template #body="{ data }"
                            ><ProductionPlanStatusBadge :status="data.status"
                        /></template>
                    </Column>
                    <Column field="quantity" :header="$t('fields.quantity')" />
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
