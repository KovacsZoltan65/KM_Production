<script setup>
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({ records: Object, filters: Object, statusOptions: Array, supplierOptions: Array });
const page = usePage();
const toast = useToast();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || null);
const supplierId = ref(props.filters.supplier_id ? Number(props.filters.supplier_id) : null);
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const sortField = ref(props.filters.sort || 'id');
const sortOrder = ref((props.filters.direction || 'asc') === 'desc' ? -1 : 1);
const query = (pageNumber = 1) => ({ search: search.value || undefined, status: status.value || undefined, supplier_id: supplierId.value || undefined, per_page: perPage.value, page: pageNumber, sort: sortField.value, direction: sortOrder.value === -1 ? 'desc' : 'asc' });
const reload = (pageNumber = 1) => router.get(route('admin.purchase-orders.index'), query(pageNumber), { preserveState: true, replace: true });
const severity = (value) => ({ ordered: 'info', partially_received: 'warn', received: 'success', cancelled: 'danger' }[value] || 'secondary');
const dateValue = (value) => (value ? String(value).slice(0, 10) : '-');
const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head :title="trans('procurement.purchase_orders.title')" />
    <AdminLayout>
        <Toast />
        <div class="space-y-4">
            <AdminPageHeader title="" title-key="procurement.purchase_orders.title" subtitle-key="procurement.purchase_orders.subtitle" :can-create="false" />
            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />
            <div class="grid gap-3 rounded border border-slate-200 bg-white p-3 md:grid-cols-2">
                <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" :placeholder="trans('filters.status')" show-clear @change="reload(1)" />
                <Select v-model="supplierId" :options="supplierOptions" option-label="label" option-value="id" :placeholder="trans('fields.supplier')" show-clear filter @change="reload(1)" />
            </div>
            <DataTable :value="records.data" lazy paginator :rows="records.per_page" :first="(records.current_page - 1) * records.per_page" :total-records="records.total" :sort-field="sortField" :sort-order="sortOrder" data-key="id" class="rounded border border-slate-200 bg-white" @page="(event) => { perPage = event.rows; reload(event.page + 1); }" @sort="(event) => { sortField = event.sortField; sortOrder = event.sortOrder; reload(1); }">
                <Column field="order_number" :header="trans('fields.order')" sortable><template #body="{ data }"><Link :href="route('admin.purchase-orders.show', data.id)" class="font-medium text-blue-700 hover:underline">{{ data.order_number }}</Link></template></Column>
                <Column :header="trans('fields.supplier')"><template #body="{ data }">{{ data.supplier?.name || '-' }}</template></Column>
                <Column field="status" :header="trans('fields.status')" sortable><template #body="{ data }"><Tag :value="trans(`status.${data.status}`)" :severity="severity(data.status)" /></template></Column>
                <Column field="items_count" :header="trans('fields.items')" />
                <Column field="expected_delivery_date" :header="trans('fields.expected')" sortable><template #body="{ data }">{{ dateValue(data.expected_delivery_date) }}</template></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
