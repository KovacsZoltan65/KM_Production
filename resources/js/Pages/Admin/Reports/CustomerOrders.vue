<script setup>
import ReportFilterBar from "@/Components/ReportFilterBar.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, router } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import DatePicker from "primevue/datepicker";
import Select from "primevue/select";
import Tag from "primevue/tag";
import { ref } from "vue";

const props = defineProps({
    report: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, required: true },
    customerOptions: { type: Array, required: true },
});

const status = ref(props.filters.status || null);
const customerId = ref(Number(props.filters.customer_id) || null);
const dateFrom = ref(props.filters.date_from ? new Date(props.filters.date_from) : null);
const dateTo = ref(props.filters.date_to ? new Date(props.filters.date_to) : null);

const formatDate = (value) =>
    value ? new Date(value).toISOString().slice(0, 10) : undefined;
const apply = () =>
    router.get(
        route("admin.reports.customer-orders"),
        {
            status: status.value || undefined,
            customer_id: customerId.value || undefined,
            date_from: formatDate(dateFrom.value),
            date_to: formatDate(dateTo.value),
        },
        { preserveState: true, replace: true }
    );
const reset = () => router.get(route("admin.reports.customer-orders"));
const typeLabel = (value) => String(value || "").replaceAll("_", " ");
</script>

<template>
    <Head :title="$t('reports.customer_orders.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("reports.customer_orders.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("reports.customer_orders.subtitle") }}
                </p>
            </div>
            <ReportFilterBar @apply="apply" @reset="reset">
                <Select
                    v-model="status"
                    :options="props.statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('filters.status')"
                    show-clear
                    class="w-full"
                />
                <Select
                    v-model="customerId"
                    :options="props.customerOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="$t('fields.customer')"
                    show-clear
                    filter
                    class="w-full"
                />
                <DatePicker
                    v-model="dateFrom"
                    show-icon
                    date-format="yy-mm-dd"
                    :placeholder="$t('filters.created_from')"
                    class="w-full"
                />
                <DatePicker
                    v-model="dateTo"
                    show-icon
                    date-format="yy-mm-dd"
                    :placeholder="$t('filters.created_to')"
                    class="w-full"
                />
            </ReportFilterBar>
            <DataTable
                :value="props.report.rows"
                data-key="id"
                class="rounded border border-slate-200 bg-white"
            >
                <Column
                    field="order_number"
                    :header="$t('reports.columns.order_number')"
                    sortable
                />
                <Column field="customer" :header="$t('fields.customer')" sortable />
                <Column field="status" :header="$t('fields.status')" sortable>
                    <template #body="{ data }"
                        ><Tag :value="typeLabel(data.status)" class="capitalize"
                    /></template>
                </Column>
                <Column field="created" :header="$t('fields.created')" sortable>
                    <template #body="{ data }">{{
                        String(data.created).slice(0, 10)
                    }}</template>
                </Column>
                <Column
                    field="requested_delivery"
                    :header="$t('reports.columns.requested_delivery')"
                    sortable
                />
                <Column
                    field="days_open"
                    :header="$t('reports.columns.days_open')"
                    sortable
                />
            </DataTable>
        </div>
    </AdminLayout>
</template>
