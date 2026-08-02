<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";
import { notifyRequestError } from "@/Composables/useRequestError";
import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import { ref } from "vue";

/** @typedef {{id: number, code: string, name: string, tax_number: string|null, email: string|null, phone: string|null, billing_address: string|null, shipping_address: string|null, notes: string|null, is_active: boolean}} CustomerRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {CustomerRecord[]} data Az aktuális oldal vevőrekordjai.
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
 * @property {PaginatedResult} records A(z) records bemeneti értéke.
 * @property {PageFilters} filters A(z) filters bemeneti értéke.
 */
/** @type {Props} */
defineProps({
    records: Object,
    filters: Object,
});

const toast = useToast();
const refreshing = ref(false);

const refreshRecords = () => {
    if (refreshing.value) return;

    router.reload({
        only: ["records"],
        preserveState: true,
        preserveScroll: true,
        onStart: () => (refreshing.value = true),
        onError: (error) =>
            notifyRequestError(toast, error, {
                fallbackKey: "notifications.error.refresh_failed",
            }),
        onFinish: () => (refreshing.value = false),
    });
};

const columns = [
    { field: "code", headerKey: "fields.code" },
    { field: "name", headerKey: "fields.name" },
    { field: "tax_number", headerKey: "fields.tax_number" },
    { field: "email", headerKey: "fields.email" },
    { field: "phone", headerKey: "fields.phone" },
    { field: "is_active", headerKey: "fields.status", type: "status" },
];

const fields = [
    {
        name: "code",
        labelKey: "fields.code",
        type: "text",
        required: true,
        immutableOnEdit: true,
        generateCode: { type: "customer" },
        icon: "hashtag",
    },
    { name: "name", labelKey: "fields.name", type: "text", icon: "tag" },
    { name: "tax_number", labelKey: "fields.tax_number", type: "text" },
    {
        name: "email",
        labelKey: "fields.email",
        type: "email",
        layoutGroup: "media",
        icon: "envelope",
    },
    {
        name: "phone",
        labelKey: "fields.phone",
        type: "text",
        layoutGroup: "media",
        icon: "phone",
    },
    {
        name: "billing_address",
        labelKey: "fields.billing_address",
        type: "textarea",
    },
    {
        name: "shipping_address",
        labelKey: "fields.shipping_address",
        type: "textarea",
    },
    { name: "notes", labelKey: "fields.notes", type: "textarea" },
    {
        name: "is_active",
        labelKey: "status.active",
        type: "checkbox",
        default: true,
    },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.customers.title"
        subtitle-key="master_data.customers.subtitle"
        route-name="admin.customers"
        create-label-key="master_data.customers.create"
        :records="records"
        :filters="filters"
        :columns="columns"
        :fields="fields"
    >
        <template #header-actions>
            <Button
                type="button"
                :label="trans('actions.refresh')"
                icon="pi pi-refresh"
                severity="secondary"
                outlined
                :loading="refreshing"
                :disabled="refreshing"
                data-test="refresh-records"
                @click="refreshRecords"
            />
        </template>
    </AdminCrudPage>
</template>
