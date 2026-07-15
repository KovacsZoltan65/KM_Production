<script setup>
import AdminPageHeader from "@/Components/Admin/AdminPageHeader.vue";
import AdminSearchBar from "@/Components/Admin/AdminSearchBar.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, router } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import DatePicker from "primevue/datepicker";
import Select from "primevue/select";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

/** @typedef {{label: string, value: string}} MovementTypeOption */
/** @typedef {{id: number, label: string}} EntityOption */
/** @typedef {{id: number, quantity: string|number, movement_type: string, source_type: string|null, source_id: number|null, performed_at: string|null, item: {item_number: string, name: string}|null, item_batch: {batch_number: string}|null, item_instance: {serial_number: string}|null, from_location: {code: string}|null, to_location: {code: string}|null, performer: {name: string}|null}} StockMovementRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {StockMovementRecord[]} data Az aktuális oldal készletmozgásai.
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
 * @property {PaginatedResult} records A lapozott készletmozgások.
 * @property {PageFilters} filters Az aktív listaszűrők.
 * @property {MovementTypeOption[]} movementTypeOptions A választható mozgástípusok.
 * @property {EntityOption[]} itemOptions A választható cikkek.
 * @property {EntityOption[]} locationOptions A választható raktárhelyek.
 */
/** @type {Props} */
const props = defineProps({
    records: Object,
    filters: Object,
    movementTypeOptions: Array,
    itemOptions: Array,
    locationOptions: Array,
});

const search = ref(props.filters.search || "");
const perPage = ref(
    Number(props.filters.per_page || props.records.per_page || 10),
);
const movementType = ref(props.filters.movement_type || null);
const itemId = ref(
    props.filters.item_id ? Number(props.filters.item_id) : null,
);
const locationId = ref(
    props.filters.location_id ? Number(props.filters.location_id) : null,
);
const dateFrom = ref(
    props.filters.date_from ? new Date(props.filters.date_from) : null,
);
const dateTo = ref(
    props.filters.date_to ? new Date(props.filters.date_to) : null,
);
const sortField = ref(props.filters.sort || "performed_at");
const sortOrder = ref((props.filters.direction || "desc") === "asc" ? 1 : -1);

const formatDate = (value) => (value ? new Date(value).toLocaleString() : "-");
const number = (value) => Number(value || 0).toFixed(3);
const isoDate = (value) =>
    value ? value.toISOString().slice(0, 10) : undefined;
const movementTypeChoices = computed(() =>
    props.movementTypeOptions.map((option) => ({
        ...option,
        label: trans(`enum.stock_movement_type.${option.value}`),
    })),
);

const query = (page = 1) => ({
    search: search.value || undefined,
    per_page: perPage.value,
    page,
    sort: sortField.value,
    direction: sortOrder.value === -1 ? "desc" : "asc",
    movement_type: movementType.value || undefined,
    item_id: itemId.value || undefined,
    location_id: locationId.value || undefined,
    date_from: isoDate(dateFrom.value),
    date_to: isoDate(dateTo.value),
});

const reload = (page = 1) =>
    router.get(route("admin.inventory.stock-movements.index"), query(page), {
        preserveState: true,
        replace: true,
    });
const onPage = (event) => {
    perPage.value = event.rows;
    reload(event.page + 1);
};
const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    reload(1);
};
</script>

<template>
    <Head :title="trans('inventory.stock_movements.title')" />

    <AdminLayout>
        <div class="space-y-4">
            <AdminPageHeader
                title=""
                title-key="inventory.stock_movements.title"
                subtitle-key="inventory.stock_movements.subtitle"
                :can-create="false"
            />
            <AdminSearchBar
                v-model="search"
                v-model:per-page="perPage"
                @search="reload(1)"
            />

            <div
                class="grid gap-3 rounded border border-slate-200 bg-white p-3 md:grid-cols-5"
            >
                <Select
                    v-model="movementType"
                    :options="movementTypeChoices"
                    option-label="label"
                    option-value="value"
                    :placeholder="trans('filters.movement_type')"
                    show-clear
                    @change="reload(1)"
                />
                <Select
                    v-model="itemId"
                    :options="itemOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="trans('fields.item')"
                    show-clear
                    filter
                    @change="reload(1)"
                />
                <Select
                    v-model="locationId"
                    :options="locationOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="trans('fields.location')"
                    show-clear
                    filter
                    @change="reload(1)"
                />
                <DatePicker
                    v-model="dateFrom"
                    date-format="yy-mm-dd"
                    :placeholder="trans('filters.from')"
                    show-icon
                    @date-select="reload(1)"
                />
                <DatePicker
                    v-model="dateTo"
                    date-format="yy-mm-dd"
                    :placeholder="trans('filters.to')"
                    show-icon
                    @date-select="reload(1)"
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
                <Column :header="trans('fields.item')" field="item_id" sortable>
                    <template #body="{ data }"
                        >{{ data.item?.item_number }} -
                        {{ data.item?.name }}</template
                    >
                </Column>
                <Column :header="trans('fields.batch')" field="item_batch_id"
                    ><template #body="{ data }">{{
                        data.item_batch?.batch_number || "-"
                    }}</template></Column
                >
                <Column
                    :header="trans('fields.instance')"
                    field="item_instance_id"
                    ><template #body="{ data }">{{
                        data.item_instance?.serial_number || "-"
                    }}</template></Column
                >
                <Column :header="trans('filters.from')" field="from_location_id"
                    ><template #body="{ data }">{{
                        data.from_location?.code || "-"
                    }}</template></Column
                >
                <Column :header="trans('filters.to')" field="to_location_id"
                    ><template #body="{ data }">{{
                        data.to_location?.code || "-"
                    }}</template></Column
                >
                <Column
                    :header="trans('fields.quantity_short')"
                    field="quantity"
                    sortable
                    ><template #body="{ data }">{{
                        number(data.quantity)
                    }}</template></Column
                >
                <Column
                    :header="trans('fields.type')"
                    field="movement_type"
                    sortable
                    ><template #body="{ data }">{{
                        trans(`enum.stock_movement_type.${data.movement_type}`)
                    }}</template></Column
                >
                <Column :header="trans('fields.source')"
                    ><template #body="{ data }"
                        >{{ data.source_type || "-" }} #{{
                            data.source_id || "-"
                        }}</template
                    ></Column
                >
                <Column :header="trans('fields.by')"
                    ><template #body="{ data }">{{
                        data.performer?.name || "-"
                    }}</template></Column
                >
                <Column
                    :header="trans('fields.at')"
                    field="performed_at"
                    sortable
                    ><template #body="{ data }">{{
                        formatDate(data.performed_at)
                    }}</template></Column
                >
            </DataTable>
        </div>
    </AdminLayout>
</template>
