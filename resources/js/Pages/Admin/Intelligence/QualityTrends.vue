<script setup>
import TrendIndicator from "@/Components/TrendIndicator.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

defineProps({ trends: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.quality_trends.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.quality_trends.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.quality_trends.subtitle") }}
                </p>
            </div>
            <DataTable
                :value="trends.rows"
                class="rounded border border-slate-200 bg-white"
            >
                <Column field="item" :header="$t('fields.item')" sortable />
                <Column
                    field="production_order"
                    :header="$t('fields.production_order')"
                    sortable
                />
                <Column
                    field="accepted_count"
                    :header="$t('enum.quality_check_result.accepted')"
                    sortable
                />
                <Column
                    field="rework_count"
                    :header="$t('enum.quality_check_result.rework_required')"
                    sortable
                />
                <Column
                    field="rejected_count"
                    :header="$t('enum.quality_check_result.rejected')"
                    sortable
                />
                <Column
                    field="defect_rate"
                    :header="$t('intelligence.columns.defect_percent')"
                    sortable
                />
                <Column field="trend" :header="$t('fields.trend')" sortable
                    ><template #body="{ data }"
                        ><TrendIndicator :value="data.trend" /></template
                ></Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
