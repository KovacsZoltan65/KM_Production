<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CustomerOrderStatusBadge from "@/Pages/Admin/CustomerOrders/Partials/CustomerOrderStatusBadge.vue";
import { route } from "@/Utils/routes";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { trans } from "laravel-vue-i18n";
import { onMounted, watch } from "vue";

/** @typedef {{id: number, code: string, name: string}} Customer */
/** @typedef {{id: number, item_number: string, name: string, unit: string}} Item */
/** @typedef {{id: number, quantity: string, unit: string, status: string, notes: string|null, item: Item|null}} CustomerOrderItem */
/** @typedef {{label: string, value: string}} StatusOption */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ customer: Customer|null, id: number, items: CustomerOrderItem[], items_count: number, notes: string|null, order_number: string, requested_delivery_date: string|null, status: string }} customerOrder A megjelenített vevői rendelés.
 * @property {StatusOption[]} statusOptions A választható rendelésállapotok.
 */
/** @type {Props} */
const props = defineProps({
    customerOrder: Object,
    statusOptions: Array,
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

const dateValue = (value) => (value ? String(value).slice(0, 10) : "-");
const canConfirm = () => props.customerOrder.status === "draft";
const canCancel = () =>
    !["completed", "cancelled"].includes(props.customerOrder.status);

const confirmOrder = () => {
    confirm.require({
        message: trans("orders.confirm.confirm_message", {
            name: props.customerOrder.order_number,
        }),
        header: trans("orders.confirm.confirm_header"),
        icon: "pi pi-check-circle",
        accept: () =>
            router.patch(
                route("admin.customer-orders.confirm", props.customerOrder.id),
                {},
                { preserveScroll: true },
            ),
    });
};

const cancelOrder = () => {
    confirm.require({
        message: trans("orders.confirm.cancel_message", {
            name: props.customerOrder.order_number,
        }),
        header: trans("orders.confirm.cancel_header"),
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () =>
            router.patch(
                route("admin.customer-orders.cancel", props.customerOrder.id),
                {},
                { preserveScroll: true },
            ),
    });
};

onMounted(() => {
    if (page.props.flash?.success) {
        toast.add({
            severity: "success",
            summary: page.props.flash.success,
            life: 2500,
        });
    }
});

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({ severity: "success", summary: message, life: 2500 });
        }
    },
);
</script>

<template>
    <Head :title="customerOrder.order_number" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-2">
                    <Link
                        :href="route('admin.customer-orders.index')"
                        class="text-sm text-blue-700 hover:underline"
                        >{{ trans("orders.actions.back_to_orders") }}</Link
                    >
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">
                            {{ customerOrder.order_number }}
                        </h1>
                        <CustomerOrderStatusBadge
                            :status="customerOrder.status"
                        />
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ customerOrder.customer?.code }} -
                        {{ customerOrder.customer?.name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="canConfirm()"
                        type="button"
                        :label="trans('actions.confirm')"
                        icon="pi pi-check"
                        severity="success"
                        @click="confirmOrder"
                    />
                    <Button
                        v-if="canCancel()"
                        type="button"
                        :label="trans('actions.cancel')"
                        icon="pi pi-ban"
                        severity="warning"
                        outlined
                        @click="cancelOrder"
                    />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ trans("fields.customer") }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{ customerOrder.customer?.name }}
                    </div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ trans("orders.fields.requested_delivery") }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{ dateValue(customerOrder.requested_delivery_date) }}
                    </div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">
                        {{ trans("fields.items") }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{ customerOrder.items_count }}
                    </div>
                </div>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="mb-2 text-sm font-semibold">
                    {{ trans("fields.notes") }}
                </div>
                <p class="whitespace-pre-line text-sm text-slate-700">
                    {{ customerOrder.notes || "-" }}
                </p>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">
                    {{ trans("orders.items.title") }}
                </h2>
                <DataTable :value="customerOrder.items" data-key="id">
                    <Column :header="trans('fields.item')">
                        <template #body="{ data }"
                            >{{ data.item?.item_number }} -
                            {{ data.item?.name }}</template
                        >
                    </Column>
                    <Column
                        field="quantity"
                        :header="trans('fields.quantity')"
                    />
                    <Column field="unit" :header="trans('fields.unit')" />
                    <Column field="status" :header="trans('fields.status')">
                        <template #body="{ data }"
                            ><CustomerOrderStatusBadge :status="data.status"
                        /></template>
                    </Column>
                    <Column field="notes" :header="trans('fields.notes')" />
                </DataTable>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold">
                        {{ trans("orders.related.production_plans") }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ trans("orders.related.production_plans_help") }}
                    </p>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold">
                        {{ trans("orders.related.material_requirements") }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ trans("orders.related.material_requirements_help") }}
                    </p>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold">
                        {{ trans("orders.related.production_orders") }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ trans("orders.related.production_orders_help") }}
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
