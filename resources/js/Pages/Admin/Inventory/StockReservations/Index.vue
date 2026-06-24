<script setup>
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({ records: Object, filters: Object, statusOptions: Array });
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const search = ref(props.filters.search || '');
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const status = ref(props.filters.status || null);
const sortField = ref(props.filters.sort || 'reserved_at');
const sortOrder = ref((props.filters.direction || 'desc') === 'asc' ? 1 : -1);

const number = (value) => Number(value || 0).toFixed(3);
const formatDate = (value) => (value ? new Date(value).toLocaleString() : '-');
const query = (pageNumber = 1) => ({ search: search.value || undefined, per_page: perPage.value, page: pageNumber, sort: sortField.value, direction: sortOrder.value === -1 ? 'desc' : 'asc', status: status.value || undefined });
const reload = (pageNumber = 1) => router.get(route('admin.inventory.stock-reservations.index'), query(pageNumber), { preserveState: true, replace: true });
const onPage = (event) => { perPage.value = event.rows; reload(event.page + 1); };
const onSort = (event) => { sortField.value = event.sortField; sortOrder.value = event.sortOrder; reload(1); };
const release = (record) => confirm.require({ message: 'Release this reservation?', header: 'Confirm release', icon: 'pi pi-exclamation-triangle', accept: () => router.patch(route('admin.inventory.stock-reservations.release', record.id), {}, { preserveScroll: true }) });

onMounted(() => page.props.flash?.success && toast.add({ severity: 'success', summary: page.props.flash.success, life: 2500 }));
watch(() => page.props.flash?.success, (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 }));
</script>

<template>
    <Head title="Stock Reservations" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />
        <div class="space-y-4">
            <AdminPageHeader title="Stock Reservations" subtitle="Review and release active stock reservations." :can-create="false" />
            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />
            <div class="rounded border border-slate-200 bg-white p-3">
                <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" placeholder="Status" show-clear class="w-full md:w-64" @change="reload(1)" />
            </div>

            <DataTable :value="records.data" lazy paginator :rows="records.per_page" :first="(records.current_page - 1) * records.per_page" :total-records="records.total" :sort-field="sortField" :sort-order="sortOrder" data-key="id" class="rounded border border-slate-200 bg-white" @page="onPage" @sort="onSort">
                <Column header="Item" field="item_id" sortable><template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template></Column>
                <Column header="Location"><template #body="{ data }">{{ data.location?.code || '-' }}</template></Column>
                <Column header="Batch"><template #body="{ data }">{{ data.item_batch?.batch_number || '-' }}</template></Column>
                <Column header="Customer order item"><template #body="{ data }">{{ data.customer_order_item?.customer_order?.order_number || '-' }}</template></Column>
                <Column header="Production order"><template #body="{ data }">{{ data.production_order?.order_number || '-' }}</template></Column>
                <Column header="Qty" field="reserved_quantity" sortable><template #body="{ data }">{{ number(data.reserved_quantity) }}</template></Column>
                <Column header="Status" field="status" sortable><template #body="{ data }"><Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'secondary'" /></template></Column>
                <Column header="By"><template #body="{ data }">{{ data.reserver?.name || '-' }}</template></Column>
                <Column header="Reserved at" field="reserved_at" sortable><template #body="{ data }">{{ formatDate(data.reserved_at) }}</template></Column>
                <Column header="Released at"><template #body="{ data }">{{ formatDate(data.released_at) }}</template></Column>
                <Column header=""><template #body="{ data }"><Button v-if="data.status === 'active'" icon="pi pi-undo" label="Release" severity="secondary" outlined size="small" @click="release(data)" /></template></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
