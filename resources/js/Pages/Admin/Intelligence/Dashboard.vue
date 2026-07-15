<script setup>
import BottleneckTable from "@/Components/BottleneckTable.vue";
import IntelligenceMetricCard from "@/Components/IntelligenceMetricCard.vue";
import MaterialRiskTable from "@/Components/MaterialRiskTable.vue";
import RecommendationCard from "@/Components/RecommendationCard.vue";
import RiskBadge from "@/Components/RiskBadge.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import Column from "primevue/column";
import DataTable from "primevue/datatable";

/**
 * Kapacitási szűk keresztmetszet sora.
 * @typedef {Object} BottleneckRow
 * @property {string} factory_unit A gyártóegység neve.
 * @property {number} reserved_minutes A lefoglalt idő percben.
 * @property {number} available_minutes A rendelkezésre álló idő percben.
 * @property {number} utilization_percent A kihasználtság százalékban.
 * @property {number} queue_length A várakozó feladatok száma.
 * @property {number} average_task_duration Az átlagos feladatidő percben.
 * @property {number} late_related_orders A kapcsolódó késő rendelések száma.
 * @property {string} status A kockázati állapot.
 */
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
 * Beszállítói teljesítménysor.
 * @typedef {Object} SupplierPerformanceRow
 * @property {string} supplier A beszállító neve.
 * @property {number|null} average_delivery_days Az átlagos szállítási idő napban.
 * @property {number} late_delivery_count A késedelmes szállítások száma.
 * @property {number|null} on_time_rate A határidőre szállítás aránya.
 */
/**
 * Minőségügyi kockázatot jelző termék.
 * @typedef {Object} QualityRiskProduct
 * @property {string} item A cikk megnevezése.
 * @property {string} production_order A gyártási rendelés száma.
 * @property {number} defect_rate A hibaarány százalékban.
 * @property {string} trend A hibaarány trendje.
 */
/**
 * Magas kockázatú vevői rendelés.
 * @typedef {Object} HighRiskOrder
 * @property {string} customer_order A rendelés száma.
 * @property {string} customer A vevő neve.
 * @property {number|null} days_until_requested_delivery A kért szállításig hátralévő napok.
 * @property {number} risk_score A kockázati pontszám.
 * @property {string} risk_level A kockázati szint.
 */
/**
 * Beszerzési ajánlás.
 * @typedef {Object} ProcurementRecommendation
 * @property {number} item_id A cikk azonosítója.
 * @property {string} item A cikk megnevezése.
 * @property {string} unit A mértékegység.
 * @property {number} recommended_quantity A javasolt mennyiség.
 * @property {string} reason A javaslat indoklása.
 * @property {string} risk_level A kockázati szint.
 * @property {string[]} related_customer_orders A kapcsolódó rendelések.
 */
/**
 * Átfutásiidő-pontosság.
 * @typedef {Object} LeadTimeAccuracy
 * @property {boolean} enough_data Van-e elegendő historikus adat.
 * @property {string} [message] Az adathiány üzenete.
 * @property {number} [average_delay] Az átlagos késés napban.
 * @property {number} [on_time_rate] A határidőre teljesítés aránya.
 * @property {number} [early_completions] A korai teljesítések száma.
 * @property {number} [late_completions] A késő teljesítések száma.
 */

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ bottleneck_factory_units: BottleneckRow[], high_risk_orders: HighRiskOrder[], materials_at_risk: MaterialRiskRow[], quality_risk_products: QualityRiskProduct[], recommended_purchases: ProcurementRecommendation[], slow_suppliers: SupplierPerformanceRow[], lead_time_accuracy: LeadTimeAccuracy }} dashboard A gyártási intelligencia irányítópult adatai.
 */
/** @type {Props} */
defineProps({ dashboard: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.dashboard.title')" />
    <AdminLayout>
        <div class="space-y-5">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.dashboard.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.dashboard.subtitle") }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <IntelligenceMetricCard
                    :title="$t('intelligence.kpi.high_risk_orders')"
                    :value="dashboard.high_risk_orders.length"
                />
                <IntelligenceMetricCard
                    :title="$t('intelligence.kpi.materials_at_risk')"
                    :value="dashboard.materials_at_risk.length"
                />
                <IntelligenceMetricCard
                    :title="$t('intelligence.kpi.recommended_purchases')"
                    :value="dashboard.recommended_purchases.length"
                />
            </div>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("intelligence.sections.high_risk_orders") }}
                </h2>
                <DataTable
                    :value="dashboard.high_risk_orders"
                    class="rounded border border-slate-200 bg-white"
                >
                    <Column
                        field="customer_order"
                        :header="$t('fields.order')"
                    />
                    <Column field="customer" :header="$t('fields.customer')" />
                    <Column
                        field="days_until_requested_delivery"
                        :header="$t('intelligence.columns.days_left')"
                    />
                    <Column field="risk_score" :header="$t('fields.score')" />
                    <Column field="risk_level" :header="$t('fields.risk')"
                        ><template #body="{ data }"
                            ><RiskBadge :value="data.risk_level" /></template
                    ></Column>
                </DataTable>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("intelligence.sections.bottleneck_factory_units") }}
                </h2>
                <BottleneckTable :rows="dashboard.bottleneck_factory_units" />
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("intelligence.sections.materials_at_risk") }}
                </h2>
                <MaterialRiskTable :rows="dashboard.materials_at_risk" />
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold">
                        {{ $t("intelligence.sections.slow_suppliers") }}
                    </h2>
                    <DataTable
                        :value="dashboard.slow_suppliers"
                        class="rounded border border-slate-200 bg-white"
                    >
                        <Column
                            field="supplier"
                            :header="$t('fields.supplier')"
                        />
                        <Column
                            field="average_delivery_days"
                            :header="$t('intelligence.columns.avg_days')"
                        />
                        <Column
                            field="late_delivery_count"
                            :header="$t('intelligence.columns.late')"
                        />
                        <Column
                            field="on_time_rate"
                            :header="$t('intelligence.columns.on_time_percent')"
                        />
                    </DataTable>
                </div>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold">
                        {{ $t("intelligence.sections.quality_risk_products") }}
                    </h2>
                    <DataTable
                        :value="dashboard.quality_risk_products"
                        class="rounded border border-slate-200 bg-white"
                    >
                        <Column field="item" :header="$t('fields.item')" />
                        <Column
                            field="production_order"
                            :header="$t('fields.order')"
                        />
                        <Column
                            field="defect_rate"
                            :header="$t('intelligence.columns.defect_percent')"
                        />
                    </DataTable>
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ $t("intelligence.sections.recommended_purchases") }}
                </h2>
                <div class="grid gap-4 lg:grid-cols-2">
                    <RecommendationCard
                        v-for="item in dashboard.recommended_purchases"
                        :key="item.item_id"
                        :recommendation="item"
                    />
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
