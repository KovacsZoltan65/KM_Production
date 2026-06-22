<script setup>
import AdminActionButtons from '@/Components/Admin/AdminActionButtons.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import AdminStatusBadge from '@/Components/Admin/AdminStatusBadge.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    baseUrl: { type: String, required: true },
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, required: true },
    fields: { type: Array, default: () => [] },
    createLabel: { type: String, default: 'Create' },
    readOnly: { type: Boolean, default: false },
    options: { type: Object, default: () => ({}) },
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
const form = reactive({});
const errors = ref({});

const pageTitle = computed(() => (editingRecord.value ? `Edit ${props.title}` : props.createLabel));

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

const optionItems = (field) => {
    const source = props.options[field.options] || field.options || [];

    return source.map((option) => {
        if (typeof option === 'string' || typeof option === 'number') {
            return { label: option, value: option };
        }

        return {
            label: field.optionLabel ? option[field.optionLabel] : option.label || option.name || option.code,
            value: field.optionValue ? option[field.optionValue] : option.value ?? option.id,
        };
    });
};

const resetForm = () => {
    props.fields.forEach((field) => {
        form[field.name] = field.default ?? (field.type === 'multiselect' ? [] : field.type === 'checkbox' ? false : null);
    });
    errors.value = {};
};

const openCreate = () => {
    editingRecord.value = null;
    resetForm();
    dialogVisible.value = true;
};

const openEdit = (record) => {
    editingRecord.value = record;
    props.fields.forEach((field) => {
        if (field.type === 'multiselect') {
            form[field.name] = record[field.name] || [];
            return;
        }

        form[field.name] = record[field.name] ?? field.default ?? null;
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
    router.get(props.baseUrl, query(pageNumber), {
        preserveState: true,
        replace: true,
    });
};

const onPage = (event) => {
    perPage.value = event.rows;
    reload(event.page + 1);
};

const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    reload(1);
};

const submit = () => {
    errors.value = {};
    const payload = { ...form };
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
        router.put(`${props.baseUrl}/${editingRecord.value.id}`, payload, callbacks);
        return;
    }

    router.post(props.baseUrl, payload, callbacks);
};

const destroyRecord = (record) => {
    confirm.require({
        message: 'Delete this record?',
        header: 'Confirm delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`${props.baseUrl}/${record.id}`, { preserveScroll: true }),
    });
};

const resolveValue = (record, column) => {
    if (column.format) {
        return column.format(record);
    }

    return column.field.split('.').reduce((value, key) => value?.[key], record);
};
</script>

<template>
    <Head :title="title" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <AdminPageHeader
                :title="title"
                :subtitle="subtitle"
                :create-label="createLabel"
                :can-create="!readOnly"
                @create="openCreate"
            />

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
                @page="onPage"
                @sort="onSort"
            >
                <Column
                    v-for="column in columns"
                    :key="column.field"
                    :field="column.sortField || column.field"
                    :header="column.header"
                    :sortable="column.sortable !== false"
                >
                    <template #body="{ data }">
                        <AdminStatusBadge v-if="column.type === 'status'" :active="Boolean(resolveValue(data, column))" />
                        <span v-else>{{ resolveValue(data, column) }}</span>
                    </template>
                </Column>

                <Column v-if="!readOnly" header="" body-style="text-align: right; width: 7rem">
                    <template #body="{ data }">
                        <AdminActionButtons @edit="openEdit(data)" @delete="destroyRecord(data)" />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="dialogVisible" modal :header="pageTitle" class="w-[min(42rem,calc(100vw-2rem))]">
            <form class="space-y-4" @submit.prevent="submit">
                <div v-for="field in fields" :key="field.name" class="space-y-2">
                    <label :for="field.name" class="text-sm font-medium">{{ field.label }}</label>

                    <InputText
                        v-if="['text', 'email', 'password', 'date', 'number'].includes(field.type)"
                        :id="field.name"
                        v-model="form[field.name]"
                        :type="field.type"
                        class="w-full"
                    />
                    <Textarea
                        v-else-if="field.type === 'textarea'"
                        :id="field.name"
                        v-model="form[field.name]"
                        rows="3"
                        class="w-full"
                    />
                    <Select
                        v-else-if="field.type === 'select'"
                        :id="field.name"
                        v-model="form[field.name]"
                        :options="optionItems(field)"
                        option-label="label"
                        option-value="value"
                        show-clear
                        class="w-full"
                    />
                    <MultiSelect
                        v-else-if="field.type === 'multiselect'"
                        :id="field.name"
                        v-model="form[field.name]"
                        :options="optionItems(field)"
                        option-label="label"
                        option-value="value"
                        display="chip"
                        class="w-full"
                    />
                    <label v-else-if="field.type === 'checkbox'" class="flex items-center gap-2 text-sm">
                        <Checkbox v-model="form[field.name]" :input-id="field.name" binary />
                        <span>{{ field.checkboxLabel || field.label }}</span>
                    </label>

                    <p v-if="errors[field.name]" class="text-sm text-red-600">{{ errors[field.name] }}</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="dialogVisible = false" />
                    <Button type="submit" label="Save" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
