<script setup>
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, router } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { ref } from 'vue';

const props = defineProps({ records: Object, filters: Object, statusOptions: Array, itemOptions: Array, customerOrderOptions: Array });
const search = ref(props.filters.search || '');
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const status = ref(props.filters.status || null);
const requiredItemId = ref(props.filters.required_item_id ? Number(props.filters.required_item_id) : null);
const customerOrderId = ref(props.filters.customer_order_id ? Number(props.filters.customer_order_id) : null);
const sortField = ref(props.filters.sort || 'id');
const sortOrder = ref((props.filters.direction || 'asc') === 'desc' ? -1 : 1);

const number = (value) => Number(value || 0).toFixed(3);
const query = (page = 1) => ({ search: search.value || undefined, per_page: perPage.value, page, sort: sortField.value, direction: sortOrder.value === -1 ? 'desc' : 'asc', status: status.value || undefined, required_item_id: requiredItemId.value || undefined, customer_order_id: customerOrderId.value || undefined });
const reload = (page = 1) => router.get(route('admin.inventory.material-requirements.index'), query(page), { preserveState: true, replace: true });
const onPage = (event) => { perPage.value = event.rows; reload(event.page + 1); };
const onSort = (event) => { sortField.value = event.sortField; sortOrder.value = event.sortOrder; reload(1); };
const severity = (value) => ({ missing: 'danger', partially_available: 'warn', reserved: 'success', calculated: 'info' }[value] || 'secondary');
</script>

<template>
    <Head title="Material Requirements" />

    <AdminLayout>
        <div class="space-y-4">
            <AdminPageHeader title="Material Requirements" subtitle="Review BOM-driven material demand and availability." :can-create="false" />
            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />
            <div class="grid gap-3 rounded border border-slate-200 bg-white p-3 md:grid-cols-3">
                <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" placeholder="Status" show-clear @change="reload(1)" />
                <Select v-model="requiredItemId" :options="itemOptions" option-label="label" option-value="id" placeholder="Required item" show-clear filter @change="reload(1)" />
                <Select v-model="customerOrderId" :options="customerOrderOptions" option-label="label" option-value="id" placeholder="Customer order" show-clear filter @change="reload(1)" />
            </div>

            <DataTable :value="records.data" lazy paginator :rows="records.per_page" :first="(records.current_page - 1) * records.per_page" :total-records="records.total" :sort-field="sortField" :sort-order="sortOrder" data-key="id" class="rounded border border-slate-200 bg-white" @page="onPage" @sort="onSort">
                <Column header="Customer order item"><template #body="{ data }">{{ data.customer_order_item?.customer_order?.order_number || '-' }}</template></Column>
                <Column header="Required item" field="required_item_id" sortable><template #body="{ data }">{{ data.required_item?.item_number }} - {{ data.required_item?.name }}</template></Column>
                <Column header="Required" field="required_quantity" sortable><template #body="{ data }">{{ number(data.required_quantity) }}</template></Column>
                <Column header="Available" field="available_quantity" sortable><template #body="{ data }">{{ number(data.available_quantity) }}</template></Column>
                <Column header="Reserved" field="reserved_quantity" sortable><template #body="{ data }">{{ number(data.reserved_quantity) }}</template></Column>
                <Column header="Missing" field="missing_quantity" sortable><template #body="{ data }">{{ number(data.missing_quantity) }}</template></Column>
                <Column header="Unit" field="unit" />
                <Column header="Status" field="status" sortable><template #body="{ data }"><Tag :value="data.status" :severity="severity(data.status)" /></template></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
