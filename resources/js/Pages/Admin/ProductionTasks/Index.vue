<script setup>
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import AdminSearchBar from '@/Components/Admin/AdminSearchBar.vue';
import ProductionTaskStatusBadge from '@/Components/ProductionTaskStatusBadge.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    records: Object,
    filters: Object,
    statusOptions: Array,
    employeeOptions: Array,
    productionOrderOptions: Array,
});

const page = usePage();
const toast = useToast();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || null);
const employeeId = ref(props.filters.employee_id ? Number(props.filters.employee_id) : null);
const perPage = ref(Number(props.filters.per_page || props.records.per_page || 10));
const sortField = ref(props.filters.sort || 'id');
const sortOrder = ref((props.filters.direction || 'desc') === 'asc' ? 1 : -1);
const generateVisible = ref(false);
const generateForm = useForm({ production_order_id: null, employee_id: null });

const query = (pageNumber = 1) => ({
    search: search.value || undefined,
    status: status.value || undefined,
    employee_id: employeeId.value || undefined,
    per_page: perPage.value,
    page: pageNumber,
    sort: sortField.value,
    direction: sortOrder.value === -1 ? 'desc' : 'asc',
});

const reload = (pageNumber = 1) => router.get(route('admin.production-tasks.index'), query(pageNumber), { preserveState: true, replace: true });
const generate = () => generateForm.post(route('admin.production-tasks.generate-from-order'), { onSuccess: () => { generateVisible.value = false; generateForm.reset(); } });
const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head title="Production Tasks" />
    <AdminLayout>
        <Toast />
        <ConfirmDialog />
        <div class="space-y-4">
            <AdminPageHeader title="Production Tasks" subtitle="Generated execution tasks by serial item, operation step, and assigned employee." create-label="Generate from order" @create="generateVisible = true" />
            <AdminSearchBar v-model="search" v-model:per-page="perPage" @search="reload(1)" />
            <div class="grid gap-3 rounded border border-slate-200 bg-white p-3 md:grid-cols-2">
                <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" placeholder="Status" show-clear @change="reload(1)" />
                <Select v-model="employeeId" :options="employeeOptions" option-label="label" option-value="id" placeholder="Employee" filter show-clear @change="reload(1)" />
            </div>
            <DataTable :value="records.data" lazy paginator :rows="records.per_page" :first="(records.current_page - 1) * records.per_page" :total-records="records.total" :sort-field="sortField" :sort-order="sortOrder" data-key="id" class="rounded border border-slate-200 bg-white" @page="(event) => { perPage = event.rows; reload(event.page + 1); }" @sort="(event) => { sortField = event.sortField; sortOrder = event.sortOrder; reload(1); }">
                <Column header="Serial"><template #body="{ data }"><Link :href="route('admin.production-tasks.show', data.id)" class="font-medium text-blue-700 hover:underline">{{ data.item_instance?.serial_number }}</Link></template></Column>
                <Column header="Order"><template #body="{ data }">{{ data.production_order?.order_number }}</template></Column>
                <Column header="Operation"><template #body="{ data }">{{ data.operation_sequence_step?.step_order }}. {{ data.operation_sequence_step?.operation_type?.name }}</template></Column>
                <Column header="Employee"><template #body="{ data }">{{ data.employee?.name }}</template></Column>
                <Column field="status" header="Status" sortable><template #body="{ data }"><ProductionTaskStatusBadge :status="data.status" /></template></Column>
                <Column header="" body-style="text-align:right"><template #body="{ data }"><Button as="a" :href="route('admin.production-tasks.show', data.id)" type="button" icon="pi pi-eye" text rounded aria-label="Open" /></template></Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="generateVisible" modal header="Generate production tasks" class="w-[min(34rem,95vw)]">
            <form class="space-y-3" @submit.prevent="generate">
                <Select v-model="generateForm.production_order_id" :options="productionOrderOptions" option-label="label" option-value="id" placeholder="Production order" filter class="w-full" />
                <Select v-model="generateForm.employee_id" :options="employeeOptions" option-label="label" option-value="id" placeholder="Assigned employee" filter class="w-full" />
                <div class="flex justify-end gap-2">
                    <Button type="button" label="Cancel" text @click="generateVisible = false" />
                    <Button type="submit" label="Generate" icon="pi pi-sitemap" :loading="generateForm.processing" />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
