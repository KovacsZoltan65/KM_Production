<script setup>
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import DocumentStatusBadge from '@/Components/DocumentStatusBadge.vue';
import DocumentUploadForm from '@/Components/DocumentUploadForm.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    documentTypeOptions: { type: Array, required: true },
    documentableTypeOptions: { type: Array, required: true },
});

const page = usePage();
const toast = useToast();
const dialogVisible = ref(false);
const search = ref(props.filters.search || '');
const documentType = ref(props.filters.document_type || null);
const documentableType = ref(props.filters.documentable_type || null);
const approved = ref(props.filters.approved ?? null);
const isCurrent = ref(props.filters.is_current ?? null);
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const sortField = ref(props.filters.sort || 'id');
const sortOrder = ref((props.filters.direction || 'desc') === 'asc' ? 1 : -1);

const booleanOptions = [
    { label: 'Yes', value: '1' },
    { label: 'No', value: '0' },
];

const query = (pageNumber = 1) => ({
    search: search.value || undefined,
    document_type: documentType.value || undefined,
    documentable_type: documentableType.value || undefined,
    approved: approved.value ?? undefined,
    is_current: isCurrent.value ?? undefined,
    per_page: perPage.value,
    page: pageNumber,
    sort: sortField.value,
    direction: sortOrder.value === -1 ? 'desc' : 'asc',
});

const reload = (pageNumber = 1) => {
    router.get(route('admin.documents.index'), query(pageNumber), {
        preserveState: true,
        replace: true,
    });
};

const typeLabel = (value) => String(value || '').replaceAll('_', ' ');
const dateValue = (value) => (value ? String(value).slice(0, 16) : '-');

const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head title="Document Library" />
    <AdminLayout>
        <Toast />
        <div class="space-y-4">
            <AdminPageHeader
                title="Document Library"
                subtitle="Controlled drawings, work notes, reports, photos, and supplier documents."
                create-label="Upload document"
                @create="dialogVisible = true"
            />

            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />

            <div class="grid gap-3 rounded border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-4">
                <Select
                    v-model="documentType"
                    :options="props.documentTypeOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Document type"
                    show-clear
                    class="w-full"
                    @change="reload(1)"
                />
                <Select
                    v-model="documentableType"
                    :options="props.documentableTypeOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Linked entity"
                    show-clear
                    class="w-full"
                    @change="reload(1)"
                />
                <Select
                    v-model="approved"
                    :options="booleanOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Approved"
                    show-clear
                    class="w-full"
                    @change="reload(1)"
                />
                <Select
                    v-model="isCurrent"
                    :options="booleanOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Current"
                    show-clear
                    class="w-full"
                    @change="reload(1)"
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
                <Column field="original_filename" header="Filename" sortable>
                    <template #body="{ data }">
                        <Link :href="route('admin.documents.show', data.id)" class="font-medium text-blue-700 hover:underline">
                            {{ data.original_filename || data.title }}
                        </Link>
                        <div class="text-xs text-slate-500">{{ data.title }}</div>
                    </template>
                </Column>
                <Column field="document_type" header="Type" sortable>
                    <template #body="{ data }"><span class="capitalize">{{ typeLabel(data.document_type) }}</span></template>
                </Column>
                <Column field="version" header="Version" sortable>
                    <template #body="{ data }">v{{ data.version }}</template>
                </Column>
                <Column header="Status">
                    <template #body="{ data }"><DocumentStatusBadge :document="data" /></template>
                </Column>
                <Column header="Linked">
                    <template #body="{ data }">{{ data.documentable_type }} #{{ data.documentable_id }}</template>
                </Column>
                <Column header="Uploaded">
                    <template #body="{ data }">
                        <div>{{ data.uploader?.name || '-' }}</div>
                        <div class="text-xs text-slate-500">{{ dateValue(data.created_at) }}</div>
                    </template>
                </Column>
                <Column header="Actions">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Link :href="route('admin.documents.show', data.id)" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Open
                            </Link>
                            <a :href="route('admin.documents.download', data.id)" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Download
                            </a>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="dialogVisible" modal header="Upload Document" class="w-[min(42rem,calc(100vw-2rem))]">
            <DocumentUploadForm
                :document-type-options="props.documentTypeOptions"
                :documentable-type-options="props.documentableTypeOptions"
                @uploaded="dialogVisible = false"
                @cancel="dialogVisible = false"
            />
        </Dialog>
    </AdminLayout>
</template>
