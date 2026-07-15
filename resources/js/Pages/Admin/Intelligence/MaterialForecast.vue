<script setup>
import MaterialRiskTable from "@/Components/MaterialRiskTable.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

/**
 * Anyagkészlet-előrejelzés sora.
 * @typedef {Object} MaterialForecastRow
 * @property {number} item_id A cikk azonosítója.
 * @property {string} item A cikk megnevezése.
 * @property {string} unit A mértékegység.
 * @property {number} current_stock Az aktuális készlet.
 * @property {number} reserved_quantity A lefoglalt mennyiség.
 * @property {number} available_quantity A szabad mennyiség.
 * @property {number|null} average_daily_consumption Az átlagos napi felhasználás.
 * @property {number|null} days_until_stockout A kifogyásig hátralévő napok.
 * @property {string} risk_level A kockázati szint.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ rows: MaterialForecastRow[] }} forecast Az anyagkészlet-előrejelzés.
 */
/** @type {Props} */
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
