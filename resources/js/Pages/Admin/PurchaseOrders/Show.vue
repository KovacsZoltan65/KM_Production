<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Tag from "primevue/tag";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { trans } from "laravel-vue-i18n";
import { computed, onMounted, watch } from "vue";

/**
 * Beszerzési rendelési tétel.
 * @typedef {Object} PurchaseOrderItem
 * @property {number} id A tétel azonosítója.
 * @property {number|string} ordered_quantity A rendelt mennyiség.
 * @property {number|string} received_quantity Az átvett mennyiség.
 * @property {string} unit A mértékegység.
 * @property {string} status A tétel állapota.
 * @property {{item_number: string, name: string}|null} item A rendelt cikk.
 */
/**
 * Megjelenített beszerzési rendelés.
 * @typedef {Object} PurchaseOrderRecord
 * @property {number} id A rendelés azonosítója.
 * @property {string} order_number A rendelés száma.
 * @property {string} status A rendelés állapota.
 * @property {{name: string}|null} supplier A beszállító.
 * @property {PurchaseOrderItem[]} items A rendelés tételei.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {PurchaseOrderRecord} purchaseOrder A megjelenített beszerzési rendelés.
 */
/** @type {Props} */
const props = defineProps({ purchaseOrder: Object });
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const canApprove = computed(() => props.purchaseOrder.status === "draft");
const canClose = computed(() =>
    ["ordered", "partially_received"].includes(props.purchaseOrder.status),
);
const number = (value) => Number(value || 0).toFixed(3);
const severity = (value) =>
    ({
        ordered: "info",
        partially_received: "warn",
        received: "success",
        cancelled: "danger",
    })[value] || "secondary";
const approve = () =>
    confirm.require({
        message: trans("procurement.purchase_orders.confirm_approve_message", {
            name: props.purchaseOrder.order_number,
        }),
        header: trans("procurement.purchase_orders.confirm_approve_header"),
        icon: "pi pi-check",
        accept: () =>
            router.patch(
                route("admin.purchase-orders.approve", props.purchaseOrder.id),
            ),
    });
const close = () =>
    confirm.require({
        message: trans("procurement.purchase_orders.confirm_close_message", {
            name: props.purchaseOrder.order_number,
        }),
        header: trans("procurement.purchase_orders.confirm_close_header"),
        icon: "pi pi-lock",
        accept: () =>
            router.patch(
                route("admin.purchase-orders.close", props.purchaseOrder.id),
            ),
    });
const flash = (message) =>
    message && toast.add({ severity: "success", summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head :title="purchaseOrder.order_number" />
    <AdminLayout>
        <Toast />
        <ConfirmDialog />
        <div class="space-y-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-2">
                    <Link
                        :href="route('admin.purchase-orders.index')"
                        class="text-sm text-blue-700 hover:underline"
                        >{{
                            trans(
                                "procurement.purchase_orders.actions.back_to_orders",
                            )
                        }}</Link
                    >
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">
                            {{ purchaseOrder.order_number }}
                        </h1>
                        <Tag
                            :value="trans(`status.${purchaseOrder.status}`)"
                            :severity="severity(purchaseOrder.status)"
                        />
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ purchaseOrder.supplier?.name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="canApprove"
                        type="button"
                        :label="trans('actions.approve')"
                        icon="pi pi-check"
                        severity="success"
                        @click="approve"
                    />
                    <Button
                        v-if="canClose"
                        type="button"
                        :label="trans('actions.close')"
                        icon="pi pi-lock"
                        outlined
                        @click="close"
                    />
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <DataTable :value="purchaseOrder.items" data-key="id">
                    <Column :header="trans('fields.item')"
                        ><template #body="{ data }"
                            >{{ data.item?.item_number }} -
                            {{ data.item?.name }}</template
                        ></Column
                    >
                    <Column :header="trans('fields.ordered')"
                        ><template #body="{ data }"
                            >{{ number(data.ordered_quantity) }}
                            {{ data.unit }}</template
                        ></Column
                    >
                    <Column :header="trans('fields.received')"
                        ><template #body="{ data }"
                            >{{ number(data.received_quantity) }}
                            {{ data.unit }}</template
                        ></Column
                    >
                    <Column field="status" :header="trans('fields.status')"
                        ><template #body="{ data }"
                            ><Tag
                                :value="trans(`status.${data.status}`)"
                                :severity="severity(data.status)" /></template
                    ></Column>
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
