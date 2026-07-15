<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Tag from "primevue/tag";

/**
 * Készletriport sora.
 * @typedef {Object} InventoryReportRow
 * @property {string} item A cikk megnevezése.
 * @property {string} location A raktárhely megnevezése.
 * @property {number} current_stock Az aktuális készlet.
 * @property {number} reserved A lefoglalt mennyiség.
 * @property {number} available A szabad mennyiség.
 * @property {number} batch_count A készlettételek száma.
 * @property {boolean} is_shortage Jelzi a készlethiányt.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ rows: InventoryReportRow[] }} report A készletriport.
 */
/** @type {Props} */
defineProps({ report: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('reports.inventory.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("reports.inventory.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("reports.inventory.subtitle") }}
                </p>
            </div>
            <DataTable
                :value="report.rows"
                data-key="item"
                class="rounded border border-slate-200 bg-white"
            >
                <Column field="item" :header="$t('fields.item')" sortable />
                <Column
                    field="location"
                    :header="$t('fields.location')"
                    sortable
                />
                <Column
                    field="current_stock"
                    :header="$t('fields.current_stock')"
                    sortable
                />
                <Column
                    field="reserved"
                    :header="$t('fields.reserved')"
                    sortable
                />
                <Column
                    field="available"
                    :header="$t('fields.available')"
                    sortable
                >
                    <template #body="{ data }">
                        <Tag
                            v-if="data.is_shortage"
                            :value="data.available"
                            severity="danger"
                        />
                        <span v-else>{{ data.available }}</span>
                    </template>
                </Column>
                <Column
                    field="batch_count"
                    :header="$t('fields.batch_count')"
                    sortable
                />
            </DataTable>
        </div>
    </AdminLayout>
</template>
