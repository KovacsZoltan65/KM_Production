<script setup>
import AdminActionButtons from '@/Components/Admin/AdminActionButtons.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminStatusBadge from '@/Components/Admin/AdminStatusBadge.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BomItemsEditor from '@/Pages/Admin/Boms/Partials/BomItemsEditor.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    records: Object,
    filters: Object,
    itemOptions: Array,
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const dialogVisible = ref(false);
const editingRecord = ref(null);
const search = ref(props.filters.search || '');
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const sortField = ref(props.filters.sort || 'id');
const sortOrder = ref((props.filters.direction || 'asc') === 'desc' ? -1 : 1);
const errors = ref({});
const form = reactive({ item_id: null, version: 1, name: '', description: '', is_active: true, items: [] });

const itemChoices = computed(() => props.itemOptions.map((item) => ({ ...item, label: `${item.item_number} - ${item.name}` })));
const dialogTitle = computed(() => (editingRecord.value ? 'Edit BOM' : 'Create BOM'));

const resetForm = () => {
    Object.assign(form, { item_id: null, version: 1, name: '', description: '', is_active: true, items: [] });
    errors.value = {};
};

const normalizeBomItems = (record) =>
    (record.bom_items || []).map((item) => ({
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
        item_id: record.item_id,
        version: record.version,
        name: record.name,
        description: record.description,
        is_active: Boolean(record.is_active),
        items: normalizeBomItems(record),
    });
    errors.value = {};
    dialogVisible.value = true;
};

const query = (pageNumber = props.records.current_page || 1) => ({
    search: search.value || undefined,
    sort: sortField.value || undefined,
    direction: sortOrder.value === -1 ? 'desc' : 'asc',
    per_page: perPage.value,
    page: pageNumber,
});

const reload = (pageNumber = 1) => {
    router.get('/admin/boms', query(pageNumber), { preserveState: true, replace: true });
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
        router.put(`/admin/boms/${editingRecord.value.id}`, payload, callbacks);
        return;
    }

    router.post('/admin/boms', payload, callbacks);
};

const destroyRecord = (record) => {
    confirm.require({
        message: 'Delete this BOM?',
        header: 'Confirm delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/boms/${record.id}`, { preserveScroll: true }),
    });
};

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
    <Head title="BOMs" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <AdminPageHeader title="BOMs" subtitle="Manage bill of materials versions and component rows." create-label="Create BOM" @create="openCreate" />
            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />

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
                <Column field="name" header="Name" sortable />
                <Column field="item_id" header="Item" sortable>
                    <template #body="{ data }">{{ data.item?.item_number }} - {{ data.item?.name }}</template>
                </Column>
                <Column field="version" header="Version" sortable />
                <Column header="Rows">
                    <template #body="{ data }">{{ data.bom_items?.length || 0 }}</template>
                </Column>
                <Column field="is_active" header="Status" sortable>
                    <template #body="{ data }"><AdminStatusBadge :active="Boolean(data.is_active)" /></template>
                </Column>
                <Column header="" body-style="text-align: right; width: 7rem">
                    <template #body="{ data }"><AdminActionButtons @edit="openEdit(data)" @delete="destroyRecord(data)" /></template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="dialogVisible" modal :header="dialogTitle" class="w-[min(64rem,calc(100vw-2rem))]">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="item_id" class="text-sm font-medium">Item</label>
                        <Select id="item_id" v-model="form.item_id" :options="itemChoices" option-label="label" option-value="id" filter class="w-full" />
                        <p v-if="errors.item_id" class="text-sm text-red-600">{{ errors.item_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="version" class="text-sm font-medium">Version</label>
                        <InputText id="version" v-model="form.version" type="number" class="w-full" />
                        <p v-if="errors.version" class="text-sm text-red-600">{{ errors.version }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="name" class="text-sm font-medium">Name</label>
                        <InputText id="name" v-model="form.name" class="w-full" />
                        <p v-if="errors.name" class="text-sm text-red-600">{{ errors.name }}</p>
                    </div>
                    <label class="flex items-center gap-2 pt-8 text-sm">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4" />
                        <span>Active</span>
                    </label>
                </div>
                <div class="space-y-2">
                    <label for="description" class="text-sm font-medium">Description</label>
                    <Textarea id="description" v-model="form.description" rows="2" class="w-full" />
                </div>

                <BomItemsEditor v-model="form.items" :item-options="itemOptions" :errors="errors" />

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="dialogVisible = false" />
                    <Button type="submit" label="Save" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
