<script setup>
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({ records: Object, filters: Object, statusOptions: Array, purchaseOrderOptions: Array, itemOptions: Array, locationOptions: Array });
const page = usePage();
const toast = useToast();
const dialogVisible = ref(false);
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || null);
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const sortField = ref(props.filters.sort || 'id');
const sortOrder = ref((props.filters.direction || 'asc') === 'desc' ? -1 : 1);
const form = useForm({ purchase_order_id: null, notes: '', items: [{ item_id: null, location_id: null, quantity: 1, notes: '' }] });
const query = (pageNumber = 1) => ({ search: search.value || undefined, status: status.value || undefined, per_page: perPage.value, page: pageNumber, sort: sortField.value, direction: sortOrder.value === -1 ? 'desc' : 'asc' });
const reload = (pageNumber = 1) => router.get(route('admin.goods-receipts.index'), query(pageNumber), { preserveState: true, replace: true });
const severity = (value) => ({ posted: 'success', draft: 'secondary' }[value] || 'secondary');
const dateValue = (value) => (value ? String(value).slice(0, 10) : '-');
const submit = () => form.post(route('admin.goods-receipts.store'), { preserveScroll: true, onSuccess: () => { dialogVisible.value = false; form.reset(); form.items = [{ item_id: null, location_id: null, quantity: 1, notes: '' }]; } });
const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head title="Goods Receipts" />
    <AdminLayout>
        <Toast />
        <div class="space-y-4">
            <AdminPageHeader title="Goods Receipts" subtitle="Inbound received materials before and after posting to stock." create-label="Create receipt" @create="dialogVisible = true" />
            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />
            <div class="rounded border border-slate-200 bg-white p-3"><Select v-model="status" :options="statusOptions" option-label="label" option-value="value" placeholder="Status" show-clear class="w-full sm:w-72" @change="reload(1)" /></div>
            <DataTable :value="records.data" lazy paginator :rows="records.per_page" :first="(records.current_page - 1) * records.per_page" :total-records="records.total" :sort-field="sortField" :sort-order="sortOrder" data-key="id" class="rounded border border-slate-200 bg-white" @page="(event) => { perPage = event.rows; reload(event.page + 1); }" @sort="(event) => { sortField = event.sortField; sortOrder = event.sortOrder; reload(1); }">
                <Column field="receipt_number" header="Receipt" sortable><template #body="{ data }"><Link :href="route('admin.goods-receipts.show', data.id)" class="font-medium text-blue-700 hover:underline">{{ data.receipt_number }}</Link></template></Column>
                <Column header="Supplier"><template #body="{ data }">{{ data.purchase_order?.supplier?.name || '-' }}</template></Column>
                <Column header="Purchase Order"><template #body="{ data }">{{ data.purchase_order?.order_number || '-' }}</template></Column>
                <Column field="status" header="Status" sortable><template #body="{ data }"><Tag :value="data.status" :severity="severity(data.status)" /></template></Column>
                <Column field="received_at" header="Received" sortable><template #body="{ data }">{{ dateValue(data.received_at) }}</template></Column>
            </DataTable>
        </div>
        <Dialog v-model:visible="dialogVisible" modal header="Create Goods Receipt" class="w-[min(48rem,calc(100vw-2rem))]">
            <form class="space-y-4" @submit.prevent="submit">
                <Select v-model="form.purchase_order_id" :options="purchaseOrderOptions" option-label="label" option-value="id" placeholder="Purchase order" show-clear filter class="w-full" />
                <div v-for="(item, index) in form.items" :key="index" class="grid gap-3 rounded border border-slate-200 p-3 md:grid-cols-[1fr_1fr_8rem]">
                    <Select v-model="item.item_id" :options="itemOptions" option-label="label" option-value="id" placeholder="Item" filter />
                    <Select v-model="item.location_id" :options="locationOptions" option-label="label" option-value="id" placeholder="Location" filter />
                    <input v-model="item.quantity" type="number" min="0.001" step="0.001" class="rounded border border-slate-300 px-3 py-2" />
                </div>
                <Button type="button" label="Add line" icon="pi pi-plus" text @click="form.items.push({ item_id: null, location_id: null, quantity: 1, notes: '' })" />
                <div class="flex justify-end gap-2"><Button type="button" label="Cancel" severity="secondary" outlined @click="dialogVisible = false" /><Button type="submit" label="Create" icon="pi pi-check" /></div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
