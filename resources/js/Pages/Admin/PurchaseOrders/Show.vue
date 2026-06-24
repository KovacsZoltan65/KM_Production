<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, watch } from 'vue';

const props = defineProps({ purchaseOrder: Object });
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const canApprove = computed(() => props.purchaseOrder.status === 'draft');
const canClose = computed(() => ['ordered', 'partially_received'].includes(props.purchaseOrder.status));
const number = (value) => Number(value || 0).toFixed(3);
const severity = (value) => ({ ordered: 'info', partially_received: 'warn', received: 'success', cancelled: 'danger' }[value] || 'secondary');
const approve = () => confirm.require({ message: `Approve ${props.purchaseOrder.order_number}?`, header: 'Approve purchase order', icon: 'pi pi-check', accept: () => router.patch(route('admin.purchase-orders.approve', props.purchaseOrder.id)) });
const close = () => confirm.require({ message: `Close ${props.purchaseOrder.order_number}?`, header: 'Close purchase order', icon: 'pi pi-lock', accept: () => router.patch(route('admin.purchase-orders.close', props.purchaseOrder.id)) });
const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head :title="purchaseOrder.order_number" />
    <AdminLayout>
        <Toast />
        <ConfirmDialog />
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link :href="route('admin.purchase-orders.index')" class="text-sm text-blue-700 hover:underline">Back to purchase orders</Link>
                    <div class="flex flex-wrap items-center gap-3"><h1 class="text-2xl font-semibold">{{ purchaseOrder.order_number }}</h1><Tag :value="purchaseOrder.status" :severity="severity(purchaseOrder.status)" /></div>
                    <p class="text-sm text-slate-600">{{ purchaseOrder.supplier?.name }}</p>
                </div>
                <div class="flex gap-2">
                    <Button v-if="canApprove" type="button" label="Approve" icon="pi pi-check" severity="success" @click="approve" />
                    <Button v-if="canClose" type="button" label="Close" icon="pi pi-lock" outlined @click="close" />
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <DataTable :value="purchaseOrder.items" data-key="id">
                    <Column header="Item"><template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template></Column>
                    <Column header="Ordered"><template #body="{ data }">{{ number(data.ordered_quantity) }} {{ data.unit }}</template></Column>
                    <Column header="Received"><template #body="{ data }">{{ number(data.received_quantity) }} {{ data.unit }}</template></Column>
                    <Column field="status" header="Status"><template #body="{ data }"><Tag :value="data.status" :severity="severity(data.status)" /></template></Column>
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
