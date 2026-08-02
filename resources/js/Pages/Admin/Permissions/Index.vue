<script setup>
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";
import { notifyRequestError } from "@/Composables/useRequestError";
import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import { ref } from "vue";

/** @typedef {{id: number, name: string, guard_name: string}} PermissionRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {PermissionRecord[]} data Az aktuális oldal jogosultságai.
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
    { field: "guard_name", headerKey: "fields.guard" },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.permissions.title"
        subtitle-key="master_data.permissions.subtitle"
        route-name="admin.permissions"
        :records="records"
        :filters="filters"
        :columns="columns"
        read-only
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
