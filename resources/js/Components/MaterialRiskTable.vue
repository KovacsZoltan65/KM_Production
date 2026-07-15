<script setup>
import RiskBadge from "@/Components/RiskBadge.vue";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

/**
 * Anyagellátási kockázat sora.
 * @typedef {Object} MaterialRiskRow
 * @property {string} item A cikk megnevezése.
 * @property {number|null} current_stock Az aktuális készlet.
 * @property {number|null} reserved_quantity A lefoglalt mennyiség.
 * @property {number|null} available_quantity A szabad mennyiség.
 * @property {number|null} average_daily_consumption Az átlagos napi felhasználás.
 * @property {number|null} days_until_stockout A készlet kifogyásáig hátralévő napok.
 * @property {string} risk_level A kockázati szint.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {MaterialRiskRow[]} rows A megjelenített anyagkockázatok.
 */
/** @type {Props} */
defineProps({
    rows: { type: Array, default: () => [] },
});

const number = (value) =>
    value === null || value === undefined ? "-" : Number(value).toFixed(3);
</script>

<template>
    <DataTable :value="rows" class="rounded border border-slate-200 bg-white">
        <Column field="item" :header="$t('fields.item')" sortable />
        <Column
            field="current_stock"
            :header="$t('intelligence.columns.current')"
            sortable
            ><template #body="{ data }">{{
                number(data.current_stock)
            }}</template></Column
        >
        <Column
            field="reserved_quantity"
            :header="$t('fields.reserved')"
            sortable
            ><template #body="{ data }">{{
                number(data.reserved_quantity)
            }}</template></Column
        >
        <Column
            field="available_quantity"
            :header="$t('fields.available')"
            sortable
            ><template #body="{ data }">{{
                number(data.available_quantity)
            }}</template></Column
        >
        <Column
            field="average_daily_consumption"
            :header="$t('intelligence.columns.avg_daily_use')"
            sortable
            ><template #body="{ data }">{{
                number(data.average_daily_consumption)
            }}</template></Column
        >
        <Column
            field="days_until_stockout"
            :header="$t('intelligence.columns.days_left')"
            sortable
            ><template #body="{ data }">{{
                data.days_until_stockout ?? "-"
            }}</template></Column
        >
        <Column field="risk_level" :header="$t('fields.risk')" sortable>
            <template #body="{ data }"
                ><RiskBadge :value="data.risk_level"
            /></template>
        </Column>
    </DataTable>
</template>
