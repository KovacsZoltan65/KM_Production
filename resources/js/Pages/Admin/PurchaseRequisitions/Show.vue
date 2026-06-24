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
const approve = () => confirm.require({ message: `Approve ${props.purchaseRequisition.requisition_number}?`, header: 'Approve requisition', icon: 'pi pi-check', accept: () => router.patch(route('admin.purchase-requisitions.approve', props.purchaseRequisition.id)) });
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
                    <Link :href="route('admin.purchase-requisitions.index')" class="text-sm text-blue-700 hover:underline">Back to purchase requisitions</Link>
                    <div class="flex flex-wrap items-center gap-3"><h1 class="text-2xl font-semibold">{{ purchaseRequisition.requisition_number }}</h1><Tag :value="purchaseRequisition.status" :severity="severity(purchaseRequisition.status)" /></div>
                </div>
                <div class="flex gap-2">
                    <Button v-if="canApprove" type="button" label="Approve" icon="pi pi-check" severity="success" @click="approve" />
                    <Button v-if="canGeneratePo" type="button" label="Generate Purchase Order" icon="pi pi-shopping-cart" outlined @click="dialogVisible = true" />
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <DataTable :value="purchaseRequisition.items" data-key="id">
                    <Column header="Item"><template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template></Column>
                    <Column header="Quantity"><template #body="{ data }">{{ number(data.quantity) }} {{ data.unit }}</template></Column>
                    <Column field="status" header="Status"><template #body="{ data }"><Tag :value="data.status" /></template></Column>
                    <Column header="Sources"><template #body="{ data }">{{ data.sources?.length || (data.material_requirement_id ? 1 : 0) }}</template></Column>
                </DataTable>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">Source Requirements</h2>
                <div v-for="item in purchaseRequisition.items" :key="item.id" class="mb-4 last:mb-0">
                    <div class="mb-2 text-sm font-medium">{{ item.item?.item_number }} - {{ item.item?.name }}</div>
                    <DataTable :value="item.sources || []" data-key="id">
                        <Column header="Customer Order"><template #body="{ data }">{{ data.material_requirement?.customer_order_item?.customer_order?.order_number || '-' }}</template></Column>
                        <Column header="Quantity"><template #body="{ data }">{{ number(data.quantity) }}</template></Column>
                    </DataTable>
                </div>
            </div>
        </div>
        <Dialog v-model:visible="dialogVisible" modal header="Generate Purchase Order" class="w-[min(36rem,calc(100vw-2rem))]">
            <form class="space-y-4" @submit.prevent="generatePo">
                <Select v-model="form.supplier_id" :options="supplierOptions" option-label="label" option-value="id" placeholder="Supplier" filter class="w-full" />
                <DatePicker v-model="form.expected_delivery_date" date-format="yy-mm-dd" placeholder="Expected delivery" class="w-full" />
                <div class="flex justify-end gap-2"><Button type="button" label="Cancel" severity="secondary" outlined @click="dialogVisible = false" /><Button type="submit" label="Generate" icon="pi pi-check" /></div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
