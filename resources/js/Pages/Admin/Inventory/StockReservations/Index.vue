<script setup>
import AdminPageHeader from "@/Components/Admin/AdminPageHeader.vue";
import AdminSearchBar from "@/Components/Admin/AdminSearchBar.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, router, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Select from "primevue/select";
import Tag from "primevue/tag";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { trans } from "laravel-vue-i18n";
import { computed, onMounted, ref, watch } from "vue";

/** @typedef {{label: string, value: string}} StatusOption */
/** @typedef {{id: number, reserved_quantity: string|number, status: string, reserved_at: string|null, released_at: string|null, item: {item_number: string, name: string}|null, location: {code: string}|null, item_batch: {batch_number: string}|null, customer_order_item: {customer_order: {order_number: string}|null}|null, production_order: {order_number: string}|null, reserver: {name: string}|null}} StockReservationRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {StockReservationRecord[]} data Az aktuális oldal készletfoglalásai.
 * @property {number} current_page Az aktuális oldalszám.
 * @property {number} per_page Az oldalankénti elemszám.
 * @property {number} total A teljes elemszám.
 * @property {number} last_page Az utolsó oldalszám.
 */
/**
 * Listaoldal szerveroldali szűrői.
 * @typedef {Object} PageFilters
 * @property {string} [search] A keresőkifejezés.
 * @property {number|string} [per_page] Az oldalankénti elemszám.
 * @property {string} [sort] A rendezett mező.
 * @property {'asc'|'desc'} [direction] A rendezés iránya.
 * @property {string|number|null} [status] Az állapotszűrő.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {PaginatedResult} records A lapozott készletfoglalások.
 * @property {PageFilters} filters Az aktív listaszűrők.
 * @property {StatusOption[]} statusOptions A választható foglalási állapotok.
 */
/** @type {Props} */
const props = defineProps({
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
});
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const search = ref(props.filters.search || "");
const perPage = ref(
    Number(props.filters.per_page || props.records.per_page || 10),
);
const status = ref(props.filters.status || null);
const sortField = ref(props.filters.sort || "reserved_at");
const sortOrder = ref((props.filters.direction || "desc") === "asc" ? 1 : -1);
const processingReservationId = ref(null);
const canRelease = computed(
    () =>
        page.props.auth?.roles?.includes("super-admin") ||
        page.props.auth?.permissions?.includes("inventory.release"),
);

const number = (value) => Number(value || 0).toFixed(3);
const formatDate = (value) => (value ? new Date(value).toLocaleString() : "-");
const query = (pageNumber = 1) => ({
    search: search.value || undefined,
    per_page: perPage.value,
    page: pageNumber,
    sort: sortField.value,
    direction: sortOrder.value === -1 ? "desc" : "asc",
    status: status.value || undefined,
});
const reload = (pageNumber = 1) =>
    router.get(
        route("admin.inventory.stock-reservations.index"),
        query(pageNumber),
        { preserveState: true, replace: true },
    );
const onPage = (event) => {
    perPage.value = event.rows;
    reload(event.page + 1);
};
const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    reload(1);
};
const release = (record) => {
    if (!canRelease.value || processingReservationId.value !== null) {
        return;
    }

    confirm.require({
        message: trans("inventory.stock_reservations.confirm_release_message"),
        header: trans("inventory.stock_reservations.confirm_release_header"),
        icon: "pi pi-exclamation-triangle",
        accept: () => {
            processingReservationId.value = record.id;
            router.patch(
                route("admin.inventory.stock-reservations.release", record.id),
                {},
                {
                    preserveScroll: true,
                    onFinish: () => {
                        processingReservationId.value = null;
                    },
                },
            );
        },
    });
};

onMounted(
    () =>
        page.props.flash?.success &&
        toast.add({
            severity: "success",
            summary: page.props.flash.success,
            life: 2500,
        }),
);
watch(
    () => page.props.flash?.success,
    (message) =>
        message &&
        toast.add({ severity: "success", summary: message, life: 2500 }),
);
</script>

<template>
    <Head :title="trans('inventory.stock_reservations.title')" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />
        <div class="space-y-4">
            <AdminPageHeader
                title=""
                title-key="inventory.stock_reservations.title"
                subtitle-key="inventory.stock_reservations.subtitle"
                :can-create="false"
            />
            <AdminSearchBar
                v-model="search"
                v-model:per-page="perPage"
                @search="reload(1)"
            />
            <div class="rounded border border-slate-200 bg-white p-3">
                <Select
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="trans('filters.status')"
                    show-clear
                    class="w-full md:w-64"
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
                @page="onPage"
                @sort="onSort"
            >
                <template #empty>{{ trans("common.no_data") }}</template>
                <Column :header="trans('fields.item')" field="item_id" sortable
                    ><template #body="{ data }"
                        >{{ data.item?.item_number }} -
                        {{ data.item?.name }}</template
                    ></Column
                >
                <Column :header="trans('fields.location')"
                    ><template #body="{ data }">{{
                        data.location?.code || "-"
                    }}</template></Column
                >
                <Column :header="trans('fields.batch')"
                    ><template #body="{ data }">{{
                        data.item_batch?.batch_number || "-"
                    }}</template></Column
                >
                <Column :header="trans('fields.customer_order_item')"
                    ><template #body="{ data }">{{
                        data.customer_order_item?.customer_order
                            ?.order_number || "-"
                    }}</template></Column
                >
                <Column :header="trans('fields.production_order')"
                    ><template #body="{ data }">{{
                        data.production_order?.order_number || "-"
                    }}</template></Column
                >
                <Column
                    :header="trans('fields.quantity_short')"
                    field="reserved_quantity"
                    sortable
                    ><template #body="{ data }">{{
                        number(data.reserved_quantity)
                    }}</template></Column
                >
                <Column :header="trans('fields.status')" field="status" sortable
                    ><template #body="{ data }"
                        ><Tag
                            :value="trans(`status.${data.status}`)"
                            :severity="
                                data.status === 'active'
                                    ? 'success'
                                    : 'secondary'
                            " /></template
                ></Column>
                <Column :header="trans('fields.by')"
                    ><template #body="{ data }">{{
                        data.reserver?.name || "-"
                    }}</template></Column
                >
                <Column
                    :header="trans('fields.reserved_at')"
                    field="reserved_at"
                    sortable
                    ><template #body="{ data }">{{
                        formatDate(data.reserved_at)
                    }}</template></Column
                >
                <Column :header="trans('fields.released_at')"
                    ><template #body="{ data }">{{
                        formatDate(data.released_at)
                    }}</template></Column
                >
                <Column header=""
                    ><template #body="{ data }"
                        ><Button
                            v-if="data.status === 'active' && canRelease"
                            icon="pi pi-undo"
                            :label="trans('actions.release')"
                            severity="secondary"
                            outlined
                            size="small"
                            :loading="processingReservationId === data.id"
                            :disabled="processingReservationId !== null"
                            @click="release(data)" /></template
                ></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
