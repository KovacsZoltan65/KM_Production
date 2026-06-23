<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomerOrderStatusBadge from '@/Pages/Admin/CustomerOrders/Partials/CustomerOrderStatusBadge.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { onMounted, watch } from 'vue';

const props = defineProps({
    customerOrder: Object,
    statusOptions: Array,
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

const dateValue = (value) => (value ? String(value).slice(0, 10) : '-');
const canConfirm = () => props.customerOrder.status === 'draft';
const canCancel = () => !['completed', 'cancelled'].includes(props.customerOrder.status);

const confirmOrder = () => {
    confirm.require({
        message: `Confirm ${props.customerOrder.order_number}?`,
        header: 'Confirm customer order',
        icon: 'pi pi-check-circle',
        accept: () => router.patch(`/admin/customer-orders/${props.customerOrder.id}/confirm`, {}, { preserveScroll: true }),
    });
};

const cancelOrder = () => {
    confirm.require({
        message: `Cancel ${props.customerOrder.order_number}?`,
        header: 'Cancel customer order',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.patch(`/admin/customer-orders/${props.customerOrder.id}/cancel`, {}, { preserveScroll: true }),
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
    <Head :title="customerOrder.order_number" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link href="/admin/customer-orders" class="text-sm text-blue-700 hover:underline">Back to customer orders</Link>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">{{ customerOrder.order_number }}</h1>
                        <CustomerOrderStatusBadge :status="customerOrder.status" />
                    </div>
                    <p class="text-sm text-slate-600">{{ customerOrder.customer?.code }} - {{ customerOrder.customer?.name }}</p>
                </div>
                <div class="flex gap-2">
                    <Button v-if="canConfirm()" type="button" label="Confirm" icon="pi pi-check" severity="success" @click="confirmOrder" />
                    <Button v-if="canCancel()" type="button" label="Cancel" icon="pi pi-ban" severity="warning" outlined @click="cancelOrder" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">Customer</div>
                    <div class="mt-1 font-medium">{{ customerOrder.customer?.name }}</div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">Requested delivery</div>
                    <div class="mt-1 font-medium">{{ dateValue(customerOrder.requested_delivery_date) }}</div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <div class="text-xs font-medium uppercase text-slate-500">Items</div>
                    <div class="mt-1 font-medium">{{ customerOrder.items_count }}</div>
                </div>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="mb-2 text-sm font-semibold">Notes</div>
                <p class="whitespace-pre-line text-sm text-slate-700">{{ customerOrder.notes || '-' }}</p>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">Order items</h2>
                <DataTable :value="customerOrder.items" data-key="id">
                    <Column header="Item">
                        <template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template>
                    </Column>
                    <Column field="quantity" header="Quantity" />
                    <Column field="unit" header="Unit" />
                    <Column field="status" header="Status">
                        <template #body="{ data }"><CustomerOrderStatusBadge :status="data.status" /></template>
                    </Column>
                    <Column field="notes" header="Notes" />
                </DataTable>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold">Production Plans</h3>
                    <p class="mt-2 text-sm text-slate-500">Readonly preparation for production planning.</p>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold">Material Requirements</h3>
                    <p class="mt-2 text-sm text-slate-500">Readonly preparation for material planning.</p>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold">Production Orders</h3>
                    <p class="mt-2 text-sm text-slate-500">Readonly preparation for execution planning.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
