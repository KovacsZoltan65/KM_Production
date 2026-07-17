import { defineComponent } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import RecommendationCard from "@/Components/RecommendationCard.vue";
import RiskBadge from "@/Components/RiskBadge.vue";
import TrendIndicator from "@/Components/TrendIndicator.vue";

const TagStub = defineComponent({
    name: "Tag",
    props: ["severity", "value"],
    template: "<span>{{ value }}</span>",
});

const mountBadge = (component, value) =>
    shallowMount(component, {
        props: { value },
        global: { stubs: { Tag: TagStub } },
    });

describe("intelligence állapotkomponensek", () => {
    it.each([
        ["critical", "danger"],
        ["medium", "warn"],
        ["low", "success"],
        ["unexpected", "secondary"],
    ])("%s kockázathoz %s súlyosságot ad", (value, severity) => {
        const wrapper = mountBadge(RiskBadge, value);
        expect(wrapper.findComponent(TagStub).props()).toMatchObject({
            severity,
            value: `status.${value}`,
        });
    });

    it.each([
        ["improving", "success"],
        ["stable", "secondary"],
        ["worsening", "danger"],
    ])("%s trendet megfelelő állapottal jelenít meg", (value, severity) => {
        const wrapper = mountBadge(TrendIndicator, value);
        expect(wrapper.findComponent(TagStub).props("severity")).toBe(severity);
    });

    it("ajánlás mennyiségét és kapcsolódó rendeléseit stabilan formázza", () => {
        const wrapper = shallowMount(RecommendationCard, {
            props: {
                recommendation: {
                    item: "Alumínium lemez",
                    reason: "Várható hiány",
                    recommended_quantity: 12.5,
                    related_customer_orders: ["CO-1", "CO-2"],
                    risk_level: "high",
                    unit: "kg",
                },
            },
        });

        expect(wrapper.text()).toContain("12.500 kg");
        expect(wrapper.text()).toContain("CO-1, CO-2");
    });

    it("hiányos ajánlási adatoknál nullbiztos értékeket jelenít meg", () => {
        const wrapper = shallowMount(RecommendationCard, {
            props: {
                recommendation: {
                    item: "Ismeretlen",
                    reason: "",
                    recommended_quantity: null,
                    related_customer_orders: null,
                    risk_level: "unknown",
                    unit: "db",
                },
            },
        });

        expect(wrapper.text()).toContain("0.000 db");
        expect(wrapper.text()).toContain("-");
    });
});
