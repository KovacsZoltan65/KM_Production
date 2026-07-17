import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import StatusDonutChart from "@/Components/StatusDonutChart.vue";
import { makeChartPoint } from "../fixtures/domain.js";

describe("StatusDonutChart", () => {
    it("a saját transzformáció összegét és legendáját jeleníti meg", () => {
        const wrapper = mount(StatusDonutChart, {
            props: {
                rows: [
                    makeChartPoint({ label: "ready", value: "2" }),
                    makeChartPoint({ label: "in_progress", value: 3 }),
                ],
            },
        });
        expect(wrapper.text()).toContain("5");
        expect(wrapper.text()).toContain("in progress");
        expect(wrapper.findAll("svg circle")).toHaveLength(3);
    });

    it("üres adatnál empty state-et mutat", () => {
        const wrapper = mount(StatusDonutChart, { props: { rows: [] } });
        expect(wrapper.text()).toContain("common.no_data");
        expect(wrapper.findAll("svg circle")).toHaveLength(1);
    });

    it("prop változásakor frissíti a diagramadatot", async () => {
        const wrapper = mount(StatusDonutChart, {
            props: { rows: [makeChartPoint({ value: 1 })] },
        });
        await wrapper.setProps({ rows: [makeChartPoint({ value: 7 })] });
        expect(wrapper.text()).toContain("7");
    });
});
