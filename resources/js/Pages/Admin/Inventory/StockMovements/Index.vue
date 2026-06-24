<script setup>
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, router } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import { ref } from 'vue';

const props = defineProps({
    records: Object,
    filters: Object,
    movementTypeOptions: Array,
    itemOptions: Array,
    locationOptions: Array,
});

const search = ref(props.filters.search || '');
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const movementType = ref(props.filters.movement_type || null);
const itemId = ref(props.filters.item_id ? Number(props.filters.item_id) : null);
const locationId = ref(props.filters.location_id ? Number(props.filters.location_id) : null);
const dateFrom = ref(props.filters.date_from ? new Date(props.filters.date_from) : null);
const dateTo = ref(props.filters.date_to ? new Date(props.filters.date_to) : null);
const sortField = ref(props.filters.sort || 'performed_at');
const sortOrder = ref((props.filters.direction || 'desc') === 'asc' ? 1 : -1);

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '-');
const number = (value) => Number(value || 0).toFixed(3);
const isoDate = (value) => (value ? value.toISOString().slice(0, 10) : undefined);

const query = (page = 1) => ({
    search: search.value || undefined,
    per_page: perPage.value,
    page,
    sort: sortField.value,
    direction: sortOrder.value === -1 ? 'desc' : 'asc',
    movement_type: movementType.value || undefined,
    item_id: itemId.value || undefined,
    location_id: locationId.value || undefined,
    date_from: isoDate(dateFrom.value),
    date_to: isoDate(dateTo.value),
});

const reload = (page = 1) => router.get(route('admin.inventory.stock-movements.index'), query(page), { preserveState: true, replace: true });
const onPage = (event) => { perPage.value = event.rows; reload(event.page + 1); };
const onSort = (event) => { sortField.value = event.sortField; sortOrder.value = event.sortOrder; reload(1); };
</script>

<template>
    <Head title="Stock Movements" />

    <AdminLayout>
        <div class="space-y-4">
            <AdminPageHeader title="Stock Movements" subtitle="Review stock movement history and source traces." :can-create="false" />
            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />

            <div class="grid gap-3 rounded border border-slate-200 bg-white p-3 md:grid-cols-5">
                <Select v-model="movementType" :options="movementTypeOptions" option-label="label" option-value="value" placeholder="Movement type" show-clear @change="reload(1)" />
                <Select v-model="itemId" :options="itemOptions" option-label="label" option-value="id" placeholder="Item" show-clear filter @change="reload(1)" />
                <Select v-model="locationId" :options="locationOptions" option-label="label" option-value="id" placeholder="Location" show-clear filter @change="reload(1)" />
                <DatePicker v-model="dateFrom" date-format="yy-mm-dd" placeholder="From" show-icon @date-select="reload(1)" />
                <DatePicker v-model="dateTo" date-format="yy-mm-dd" placeholder="To" show-icon @date-select="reload(1)" />
            </div>

            <DataTable :value="records.data" lazy paginator :rows="records.per_page" :first="(records.current_page - 1) * records.per_page" :total-records="records.total" :sort-field="sortField" :sort-order="sortOrder" data-key="id" class="rounded border border-slate-200 bg-white" @page="onPage" @sort="onSort">
                <Column header="Item" field="item_id" sortable>
                    <template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template>
                </Column>
                <Column header="Batch" field="item_batch_id"><template #body="{ data }">{{ data.item_batch?.batch_number || '-' }}</template></Column>
                <Column header="Instance" field="item_instance_id"><template #body="{ data }">{{ data.item_instance?.serial_number || '-' }}</template></Column>
                <Column header="From" field="from_location_id"><template #body="{ data }">{{ data.from_location?.code || '-' }}</template></Column>
                <Column header="To" field="to_location_id"><template #body="{ data }">{{ data.to_location?.code || '-' }}</template></Column>
                <Column header="Qty" field="quantity" sortable><template #body="{ data }">{{ number(data.quantity) }}</template></Column>
                <Column header="Type" field="movement_type" sortable />
                <Column header="Source"><template #body="{ data }">{{ data.source_type || '-' }} #{{ data.source_id || '-' }}</template></Column>
                <Column header="By"><template #body="{ data }">{{ data.performer?.name || '-' }}</template></Column>
                <Column header="At" field="performed_at" sortable><template #body="{ data }">{{ formatDate(data.performed_at) }}</template></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
