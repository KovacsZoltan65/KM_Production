<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";
import { notifyRequestError } from "@/Composables/useRequestError";
import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import { ref } from "vue";

/** @typedef {{id: number, name: string, email: string, email_verified_at: string|null, roles: string[], created_at: string|null}} UserRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {UserRecord[]} data Az aktuális oldal felhasználói.
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
 * @property {{roles: string[]}} options A kiosztható szerepkörnevek.
 */
/** @type {Props} */
defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const toast = useToast();
const refreshing = ref(false);

const refreshRecords = () => {
    if (refreshing.value) {
        return;
    }

    router.reload({
        only: ["records"],
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            refreshing.value = true;
        },
        onError: (error) => {
            notifyRequestError(toast, error, {
                fallbackKey: "notifications.error.refresh_failed",
            });
        },
        onFinish: () => {
            refreshing.value = false;
        },
    });
};

const columns = [
    { field: "name", headerKey: "fields.name" },
    { field: "email", headerKey: "fields.email" },
    {
        field: "roles",
        headerKey: "fields.roles",
        sortable: false,
        format: (record) => record.roles?.join(", ") || "-",
    },
];

const fields = [
    {
        name: "name",
        labelKey: "fields.name",
        type: "text",
        icon: "tag",
    },
    {
        name: "email",
        labelKey: "fields.email",
        type: "email",
        icon: "envelope",
    },
    {
        name: "password",
        labelKey: "fields.password",
        type: "password",
    },
    {
        name: "roles",
        labelKey: "fields.roles",
        type: "multiselect",
        options: "roles",
        default: [],
    },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.users.title"
        subtitle-key="master_data.users.subtitle"
        route-name="admin.users"
        create-label-key="master_data.users.create"
        :records="records"
        :filters="filters"
        :options="options"
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
