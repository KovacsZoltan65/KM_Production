import { defineComponent } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import CapacityGauge from "@/Components/CapacityGauge.vue";
import DashboardChartCard from "@/Components/DashboardChartCard.vue";
import DashboardStatCard from "@/Components/DashboardStatCard.vue";
import EmployeeLoadTable from "@/Components/EmployeeLoadTable.vue";
import FactoryLoadTable from "@/Components/FactoryLoadTable.vue";
import IntelligenceMetricCard from "@/Components/IntelligenceMetricCard.vue";
import MaterialRiskTable from "@/Components/MaterialRiskTable.vue";
import {
    capacityTone,
    formatDashboardNumber,
    loadStatusSeverity,
} from "@/Utils/dashboard";
import { makeDashboardMetric, makeFactoryLoad } from "../fixtures/domain.js";

const DataTableStub = defineComponent({
    name: "DataTable",
    props: ["value"],
    template: "<div><slot /></div>",
});

describe("dashboard adattranszformációk", () => {
    it.each([
        [0, "0.000"],
        ["12.5", "12.500"],
        [null, "-"],
        [undefined, "-"],
        ["hibás", "-"],
    ])("%s értéket %s formában ad vissza", (value, expected) => {
        expect(formatDashboardNumber(value)).toBe(expected);
    });

    it.each([
        ["green", "success"],
        ["yellow", "warn"],
        ["red", "danger"],
        ["unknown", "secondary"],
    ])("%s terhelési státuszhoz %s severity tartozik", (status, expected) => {
        expect(loadStatusSeverity(status)).toBe(expected);
    });

    it.each([
        [0, "border-emerald-200"],
        [70, "border-amber-200"],
        [90, "border-red-200"],
    ])("%s%% kapacitáshoz megfelelő tónust választ", (value, expected) => {
        expect(capacityTone(value)).toContain(expected);
    });
});

describe("dashboard kártyák", () => {
    it("a nulla KPI értéket nem cseréli üres állapotra", () => {
        const wrapper = shallowMount(DashboardStatCard, {
            props: { label: "Hiányok", value: 0 },
        });
        expect(wrapper.text()).toContain("0");
    });

    it("intelligence metrikánál a nullaértéket és opcionális alcímet kezeli", () => {
        const metric = makeDashboardMetric({ value: 0, detail: "" });
        const wrapper = shallowMount(IntelligenceMetricCard, {
            props: {
                title: metric.label,
                value: metric.value,
                subtitle: metric.detail,
            },
        });
        expect(wrapper.text()).toContain("0");
        expect(wrapper.find(".mt-1").exists()).toBe(false);
    });

    it.each([
        [65, "border-emerald-200"],
        [75, "border-amber-200"],
        [95, "border-red-200"],
    ])("CapacityGauge %s értéknél a megfelelő állapotot jelzi", (value, tone) => {
        const wrapper = shallowMount(CapacityGauge, {
            props: { label: "Terhelés", value },
        });
        expect(wrapper.classes()).toContain(tone);
        expect(wrapper.text()).toContain(`${value}%`);
    });

    it("chart card a címet és a saját slot tartalmát adja vissza", () => {
        const wrapper = shallowMount(DashboardChartCard, {
            props: { title: "Állapotok" },
            slots: { default: "<span data-test='chart'>Diagram</span>" },
        });
        expect(wrapper.text()).toContain("Állapotok");
        expect(wrapper.find("[data-test='chart']").exists()).toBe(true);
    });
});

describe("dashboard táblák", () => {
    it("a gyártóegység-terheléseket változatlanul átadja a táblának", () => {
        const loads = [makeFactoryLoad()];
        const wrapper = shallowMount(FactoryLoadTable, {
            props: { loads },
            global: { stubs: { DataTable: DataTableStub } },
        });
        expect(wrapper.findComponent(DataTableStub).props("value")).toEqual(loads);
    });

    it("üres munkatársi terhelési listát stabilan átad", () => {
        const wrapper = shallowMount(EmployeeLoadTable, {
            props: { loads: [] },
            global: { stubs: { DataTable: DataTableStub } },
        });
        expect(wrapper.findComponent(DataTableStub).props("value")).toEqual([]);
    });

    it("részleges anyagkockázati rekordot összeomlás nélkül fogad", () => {
        const rows = [{ item: "Lemez", current_stock: null }];
        const wrapper = shallowMount(MaterialRiskTable, {
            props: { rows },
            global: { stubs: { DataTable: DataTableStub } },
        });
        expect(wrapper.findComponent(DataTableStub).props("value")).toEqual(rows);
        expect(wrapper.exists()).toBe(true);
    });
});
