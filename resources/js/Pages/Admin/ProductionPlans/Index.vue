<script setup>
import AdminActionButtons from '@/Components/Admin/AdminActionButtons.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProductionPlanForm from '@/Pages/Admin/ProductionPlans/Partials/ProductionPlanForm.vue';
import ProductionPlanStatusBadge from '@/Pages/Admin/ProductionPlans/Partials/ProductionPlanStatusBadge.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    records: Object,
    filters: Object,
    customerOrderOptions: Array,
    bomOptions: Array,
    operationSequenceOptions: Array,
    statusOptions: Array,
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const dialogVisible = ref(false);
const editingRecord = ref(null);
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || null);
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const sortField = ref(props.filters.sort || 'id');
const sortOrder = ref((props.filters.direction || 'asc') === 'desc' ? -1 : 1);
const errors = ref({});
const form = reactive({ customer_order_id: null, planned_start_date: null, planned_finish_date: null, notes: '', items: [] });

const dialogTitle = computed(() => (editingRecord.value ? 'Edit production plan' : 'Create production plan'));

const dateValue = (value) => (value ? String(value).slice(0, 10) : null);
const formatDate = (value) => dateValue(value) || '-';

const normalizeItems = (record) =>
    (record.items || []).map((item) => ({
        id: item.id,
        item_id: item.item_id,
        item_label: `${item.item?.item_number || ''} - ${item.item?.name || ''}`,
        quantity: item.quantity,
        bom_id: item.bom_id,
        operation_sequence_id: item.operation_sequence_id,
        planned_start_date: dateValue(item.planned_start_date),
        planned_finish_date: dateValue(item.planned_finish_date),
        status: item.status,
        notes: item.notes,
    }));

const resetForm = () => {
    Object.assign(form, { customer_order_id: null, planned_start_date: null, planned_finish_date: null, notes: '', items: [] });
    errors.value = {};
};

const openCreate = () => {
    editingRecord.value = null;
    resetForm();
    dialogVisible.value = true;
};

const openEdit = (record) => {
    editingRecord.value = record;
    Object.assign(form, {
        customer_order_id: record.customer_order_id,
        planned_start_date: dateValue(record.planned_start_date),
        planned_finish_date: dateValue(record.planned_finish_date),
        notes: record.notes,
        items: normalizeItems(record),
    });
    errors.value = {};
    dialogVisible.value = true;
};

const query = (pageNumber = props.records.current_page || 1) => ({
    search: search.value || undefined,
    status: status.value || undefined,
    sort: sortField.value || undefined,
    direction: sortOrder.value === -1 ? 'desc' : 'asc',
    per_page: perPage.value,
    page: pageNumber,
});

const reload = (pageNumber = 1) => {
    router.get(route('admin.production-plans.index'), query(pageNumber), { preserveState: true, replace: true });
};

const submit = () => {
    errors.value = {};
    const payload = { ...form, items: [...form.items] };
    const callbacks = {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            resetForm();
        },
        onError: (responseErrors) => {
            errors.value = responseErrors;
        },
    };

    if (editingRecord.value) {
        router.put(route('admin.production-plans.update', editingRecord.value.id), payload, callbacks);
        return;
    }

    router.post(route('admin.production-plans.store'), payload, callbacks);
};

const approvePlan = (record) => {
    confirm.require({
        message: `Approve ${record.plan_number}?`,
        header: 'Approve production plan',
        icon: 'pi pi-check-circle',
        accept: () => router.patch(route('admin.production-plans.approve', record.id), {}, { preserveScroll: true }),
    });
};

const destroyRecord = (record) => {
    confirm.require({
        message: `Delete ${record.plan_number}?`,
        header: 'Delete production plan',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.production-plans.destroy', record.id), { preserveScroll: true }),
    });
};

const canApprove = (record) => ['draft', 'calculated'].includes(record.status);

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
    <Head title="Production Plans" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <AdminPageHeader
                title="Production Plans"
                subtitle="Convert confirmed customer demand into planned production work."
                create-label="Create plan"
                @create="openCreate"
            />

            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />

            <div class="flex flex-col gap-2 rounded border border-slate-200 bg-white p-3 sm:flex-row sm:items-center">
                <label for="status" class="text-sm font-medium text-slate-700">Status</label>
                <Select
                    id="status"
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    show-clear
                    class="w-full sm:w-72"
                    @update:model-value="reload(1)"
                />
            </div>

            <DataTable
                :value="records.data"
                lazy
                paginator
                :rows="records.per_page"
                :first="(records.current_page - 1) * records.per_page"
                :total-records="records.total"
                :sort-field="sortField"
                :sort-order="sortOrder"
                data-key="id"
                class="rounded border border-slate-200 bg-white"
                @page="(event) => { perPage = event.rows; reload(event.page + 1); }"
                @sort="(event) => { sortField = event.sortField; sortOrder = event.sortOrder; reload(1); }"
            >
                <Column field="plan_number" header="Plan number" sortable>
                    <template #body="{ data }">
                        <Link :href="route('admin.production-plans.show', data.id)" class="font-medium text-blue-700 hover:underline">
                            {{ data.plan_number }}
                        </Link>
                    </template>
                </Column>
                <Column field="customer_order_id" header="Customer order">
                    <template #body="{ data }">{{ data.customer_order?.order_number }} - {{ data.customer_order?.customer?.name }}</template>
                </Column>
                <Column field="status" header="Status" sortable>
                    <template #body="{ data }"><ProductionPlanStatusBadge :status="data.status" /></template>
                </Column>
                <Column field="planned_start_date" header="Start" sortable>
                    <template #body="{ data }">{{ formatDate(data.planned_start_date) }}</template>
                </Column>
                <Column field="planned_finish_date" header="Finish" sortable>
                    <template #body="{ data }">{{ formatDate(data.planned_finish_date) }}</template>
                </Column>
                <Column field="items_count" header="Items">
                    <template #body="{ data }">{{ data.items_count }}</template>
                </Column>
                <Column field="created_at" header="Created" sortable>
                    <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                </Column>
                <Column header="" body-style="text-align: right; min-width: 10rem">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button
                                v-if="canApprove(data)"
                                type="button"
                                icon="pi pi-check"
                                severity="success"
                                text
                                rounded
                                aria-label="Approve"
                                @click="approvePlan(data)"
                            />
                            <AdminActionButtons @edit="openEdit(data)" @delete="destroyRecord(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="dialogVisible" modal :header="dialogTitle" class="w-[min(78rem,calc(100vw-2rem))]">
            <ProductionPlanForm
                :form="form"
                :customer-order-options="customerOrderOptions"
                :bom-options="bomOptions"
                :operation-sequence-options="operationSequenceOptions"
                :errors="errors"
                :is-editing="Boolean(editingRecord)"
                @submit="submit"
                @cancel="dialogVisible = false"
            />
        </Dialog>
    </AdminLayout>
</template>
