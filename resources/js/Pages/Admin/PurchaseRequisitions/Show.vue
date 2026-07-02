<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({ purchaseRequisition: Object, supplierOptions: Array });
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const dialogVisible = ref(false);
const form = useForm({ supplier_id: null, expected_delivery_date: null });
const canApprove = computed(() => ['draft', 'requested'].includes(props.purchaseRequisition.status));
const canGeneratePo = computed(() => props.purchaseRequisition.status === 'approved');
const severity = (value) => ({ approved: 'success', requested: 'info', ordered: 'warn', cancelled: 'danger' }[value] || 'secondary');
const number = (value) => Number(value || 0).toFixed(3);
const approve = () =>
    confirm.require({
        message: trans('procurement.purchase_requisitions.confirm_approve_message', { name: props.purchaseRequisition.requisition_number }),
        header: trans('procurement.purchase_requisitions.confirm_approve_header'),
        icon: 'pi pi-check',
        accept: () => router.patch(route('admin.purchase-requisitions.approve', props.purchaseRequisition.id)),
    });
const generatePo = () => form.post(route('admin.purchase-requisitions.generate-purchase-order', props.purchaseRequisition.id), { preserveScroll: true });
const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head :title="purchaseRequisition.requisition_number" />
    <AdminLayout>
        <Toast />
        <ConfirmDialog />
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link :href="route('admin.purchase-requisitions.index')" class="text-sm text-blue-700 hover:underline">{{ trans('procurement.purchase_requisitions.actions.back_to_requisitions') }}</Link>
                    <div class="flex flex-wrap items-center gap-3"><h1 class="text-2xl font-semibold">{{ purchaseRequisition.requisition_number }}</h1><Tag :value="trans(`status.${purchaseRequisition.status}`)" :severity="severity(purchaseRequisition.status)" /></div>
                </div>
                <div class="flex gap-2">
                    <Button v-if="canApprove" type="button" :label="trans('actions.approve')" icon="pi pi-check" severity="success" @click="approve" />
                    <Button v-if="canGeneratePo" type="button" :label="trans('procurement.purchase_requisitions.actions.generate_purchase_order')" icon="pi pi-shopping-cart" outlined @click="dialogVisible = true" />
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <DataTable :value="purchaseRequisition.items" data-key="id">
                    <Column :header="trans('fields.item')"><template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template></Column>
                    <Column :header="trans('fields.quantity')"><template #body="{ data }">{{ number(data.quantity) }} {{ data.unit }}</template></Column>
                    <Column field="status" :header="trans('fields.status')"><template #body="{ data }"><Tag :value="trans(`status.${data.status}`)" /></template></Column>
                    <Column :header="trans('fields.sources')"><template #body="{ data }">{{ data.sources?.length || (data.material_requirement_id ? 1 : 0) }}</template></Column>
                </DataTable>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">{{ trans('procurement.purchase_requisitions.source_requirements.title') }}</h2>
                <div v-for="item in purchaseRequisition.items" :key="item.id" class="mb-4 last:mb-0">
                    <div class="mb-2 text-sm font-medium">{{ item.item?.item_number }} - {{ item.item?.name }}</div>
                    <DataTable :value="item.sources || []" data-key="id">
                        <Column :header="trans('fields.customer_order')"><template #body="{ data }">{{ data.material_requirement?.customer_order_item?.customer_order?.order_number || '-' }}</template></Column>
                        <Column :header="trans('fields.quantity')"><template #body="{ data }">{{ number(data.quantity) }}</template></Column>
                    </DataTable>
                </div>
            </div>
        </div>
        <Dialog v-model:visible="dialogVisible" modal :header="trans('procurement.purchase_requisitions.actions.generate_purchase_order')" class="w-[min(36rem,calc(100vw-2rem))]">
            <form class="space-y-4" @submit.prevent="generatePo">
                <Select v-model="form.supplier_id" :options="supplierOptions" option-label="label" option-value="id" :placeholder="trans('fields.supplier')" filter class="w-full" />
                <DatePicker v-model="form.expected_delivery_date" date-format="yy-mm-dd" :placeholder="trans('fields.expected_delivery')" class="w-full" />
                <div class="flex justify-end gap-2"><Button type="button" :label="trans('actions.cancel')" severity="secondary" outlined @click="dialogVisible = false" /><Button type="submit" :label="trans('actions.generate')" icon="pi pi-check" /></div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
