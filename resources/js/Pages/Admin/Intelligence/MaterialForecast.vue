<script setup>
import MaterialRiskTable from "@/Components/MaterialRiskTable.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

defineProps({ forecast: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.material_forecast.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.material_forecast.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.material_forecast.subtitle") }}
                </p>
            </div>
            <MaterialRiskTable :rows="forecast.rows" />
        </div>
    </AdminLayout>
</template>
