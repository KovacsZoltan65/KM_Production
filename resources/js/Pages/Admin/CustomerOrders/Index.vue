<script setup>
import AdminActionButtons from '@/Components/Admin/AdminActionButtons.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomerOrderForm from '@/Pages/Admin/CustomerOrders/Partials/CustomerOrderForm.vue';
import CustomerOrderStatusBadge from '@/Pages/Admin/CustomerOrders/Partials/CustomerOrderStatusBadge.vue';
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
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    records: Object,
    filters: Object,
    customerOptions: Array,
    itemOptions: Array,
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
const form = reactive({ customer_id: null, requested_delivery_date: null, notes: '', items: [] });

const dialogTitle = computed(() => (editingRecord.value ? trans('orders.dialogs.edit') : trans('orders.dialogs.create')));

const resetForm = () => {
    Object.assign(form, { customer_id: null, requested_delivery_date: null, notes: '', items: [] });
    errors.value = {};
};

const dateValue = (value) => (value ? String(value).slice(0, 10) : null);

const normalizeItems = (record) =>
    (record.items || []).map((item) => ({
        item_id: item.item_id,
        quantity: item.quantity,
        unit: item.unit,
        notes: item.notes,
    }));

const openCreate = () => {
    editingRecord.value = null;
    resetForm();
    dialogVisible.value = true;
};

const openEdit = (record) => {
    editingRecord.value = record;
    Object.assign(form, {
        customer_id: record.customer_id,
        requested_delivery_date: dateValue(record.requested_delivery_date),
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
    router.get(route('admin.customer-orders.index'), query(pageNumber), { preserveState: true, replace: true });
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
        router.put(route('admin.customer-orders.update', editingRecord.value.id), payload, callbacks);
        return;
    }

    router.post(route('admin.customer-orders.store'), payload, callbacks);
};

const confirmOrder = (record) => {
    confirm.require({
        message: trans('orders.confirm.confirm_message', { name: record.order_number }),
        header: trans('orders.confirm.confirm_header'),
        icon: 'pi pi-check-circle',
        accept: () => router.patch(route('admin.customer-orders.confirm', record.id), {}, { preserveScroll: true }),
    });
};

const cancelOrder = (record) => {
    confirm.require({
        message: trans('orders.confirm.cancel_message', { name: record.order_number }),
        header: trans('orders.confirm.cancel_header'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.patch(route('admin.customer-orders.cancel', record.id), {}, { preserveScroll: true }),
    });
};

const destroyRecord = (record) => {
    confirm.require({
        message: trans('confirm.delete_named_message', { name: record.order_number }),
        header: trans('orders.confirm.delete_header'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.customer-orders.destroy', record.id), { preserveScroll: true }),
    });
};

const canConfirm = (record) => record.status === 'draft';
const canCancel = (record) => !['completed', 'cancelled'].includes(record.status);
const canDelete = (record) => ['draft', 'cancelled'].includes(record.status);

const formatDate = (value) => dateValue(value) || '-';

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
    <Head :title="trans('orders.title')" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <AdminPageHeader
                title=""
                title-key="orders.title"
                subtitle-key="orders.subtitle"
                create-label-key="orders.actions.create"
                @create="openCreate"
            />

            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />

            <div class="flex flex-col gap-2 rounded border border-slate-200 bg-white p-3 sm:flex-row sm:items-center">
                <label for="status" class="text-sm font-medium text-slate-700">{{ trans('fields.status') }}</label>
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
                <Column field="order_number" :header="trans('orders.fields.order_number')" sortable>
                    <template #body="{ data }">
                        <Link :href="route('admin.customer-orders.show', data.id)" class="font-medium text-blue-700 hover:underline">
                            {{ data.order_number }}
                        </Link>
                    </template>
                </Column>
                <Column field="customer_id" :header="trans('fields.customer')">
                    <template #body="{ data }">{{ data.customer?.code }} - {{ data.customer?.name }}</template>
                </Column>
                <Column field="status" :header="trans('fields.status')" sortable>
                    <template #body="{ data }"><CustomerOrderStatusBadge :status="data.status" /></template>
                </Column>
                <Column field="requested_delivery_date" :header="trans('orders.fields.delivery_date')" sortable>
                    <template #body="{ data }">{{ formatDate(data.requested_delivery_date) }}</template>
                </Column>
                <Column field="items_count" :header="trans('fields.items')">
                    <template #body="{ data }">{{ data.items_count }}</template>
                </Column>
                <Column field="created_at" :header="trans('fields.created')" sortable>
                    <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                </Column>
                <Column header="" body-style="text-align: right; min-width: 14rem">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button
                                v-if="canConfirm(data)"
                                type="button"
                                icon="pi pi-check"
                                severity="success"
                                text
                                rounded
                                :aria-label="trans('actions.confirm')"
                                @click="confirmOrder(data)"
                            />
                            <Button
                                v-if="canCancel(data)"
                                type="button"
                                icon="pi pi-ban"
                                severity="warning"
                                text
                                rounded
                                :aria-label="trans('actions.cancel')"
                                @click="cancelOrder(data)"
                            />
                            <AdminActionButtons :can-delete="canDelete(data)" @edit="openEdit(data)" @delete="destroyRecord(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="dialogVisible" modal :header="dialogTitle" class="w-[min(70rem,calc(100vw-2rem))]">
            <CustomerOrderForm
                :form="form"
                :customer-options="customerOptions"
                :item-options="itemOptions"
                :errors="errors"
                @submit="submit"
                @cancel="dialogVisible = false"
            />
        </Dialog>
    </AdminLayout>
</template>
