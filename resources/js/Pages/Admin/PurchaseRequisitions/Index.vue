<script setup>
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({ records: Object, filters: Object, statusOptions: Array });
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || null);
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const sortField = ref(props.filters.sort || 'id');
const sortOrder = ref((props.filters.direction || 'asc') === 'desc' ? -1 : 1);

const query = (pageNumber = 1) => ({ search: search.value || undefined, status: status.value || undefined, per_page: perPage.value, page: pageNumber, sort: sortField.value, direction: sortOrder.value === -1 ? 'desc' : 'asc' });
const reload = (pageNumber = 1) => router.get(route('admin.purchase-requisitions.index'), query(pageNumber), { preserveState: true, replace: true });
const generate = () => confirm.require({ message: trans('procurement.purchase_requisitions.confirm_generate_message'), header: trans('procurement.purchase_requisitions.confirm_generate_header'), icon: 'pi pi-list-check', accept: () => router.post(route('admin.purchase-requisitions.generate-from-material-requirements')) });
const destroyRecord = (record) => confirm.require({ message: trans('confirm.delete_named_message', { name: record.requisition_number }), header: trans('procurement.purchase_requisitions.confirm_delete_header'), icon: 'pi pi-exclamation-triangle', acceptClass: 'p-button-danger', accept: () => router.delete(route('admin.purchase-requisitions.destroy', record.id)) });
const severity = (value) => ({ approved: 'success', requested: 'info', ordered: 'warn', cancelled: 'danger' }[value] || 'secondary');
const dateValue = (value) => (value ? String(value).slice(0, 10) : '-');
const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head :title="trans('procurement.purchase_requisitions.title')" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />
        <div class="space-y-4">
            <AdminPageHeader title="" title-key="procurement.purchase_requisitions.title" subtitle-key="procurement.purchase_requisitions.subtitle" create-label-key="procurement.purchase_requisitions.generate_from_shortages" @create="generate" />
            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />
            <div class="rounded border border-slate-200 bg-white p-3">
                <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" :placeholder="trans('filters.status')" show-clear class="w-full sm:w-72" @change="reload(1)" />
            </div>
            <DataTable :value="records.data" lazy paginator :rows="records.per_page" :first="(records.current_page - 1) * records.per_page" :total-records="records.total" :sort-field="sortField" :sort-order="sortOrder" data-key="id" class="rounded border border-slate-200 bg-white" @page="(event) => { perPage = event.rows; reload(event.page + 1); }" @sort="(event) => { sortField = event.sortField; sortOrder = event.sortOrder; reload(1); }">
                <Column field="requisition_number" :header="trans('fields.requisition')" sortable><template #body="{ data }"><Link :href="route('admin.purchase-requisitions.show', data.id)" class="font-medium text-blue-700 hover:underline">{{ data.requisition_number }}</Link></template></Column>
                <Column field="status" :header="trans('fields.status')" sortable><template #body="{ data }"><Tag :value="trans(`status.${data.status}`)" :severity="severity(data.status)" /></template></Column>
                <Column field="items_count" :header="trans('fields.items')" />
                <Column field="requested_at" :header="trans('fields.requested')" sortable><template #body="{ data }">{{ dateValue(data.requested_at) }}</template></Column>
                <Column header="" body-style="text-align:right"><template #body="{ data }"><Button type="button" icon="pi pi-trash" text rounded severity="danger" :aria-label="trans('actions.delete')" @click="destroyRecord(data)" /></template></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
