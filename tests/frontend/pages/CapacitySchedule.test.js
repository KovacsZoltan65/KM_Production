import { defineComponent } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import CapacitySchedule from "@/Pages/Admin/Capacity/Schedule.vue";
import { inertiaRouter } from "../mocks/inertia.js";

const SelectStub = defineComponent({
    name: "Select",
    props: ["modelValue", "options"],
    emits: ["update:modelValue"],
    template: "<div />",
});
const ButtonStub = defineComponent({
    name: "Button",
    props: ["disabled", "label"],
    emits: ["click"],
    template: "<button :disabled='disabled' @click='$emit(\"click\")' />",
});

const mountPage = (canPlan) =>
    shallowMount(CapacitySchedule, {
        props: {
            canPlan,
            rows: [],
            productionOrders: [{ id: 4, label: "PO-4" }],
        },
        global: { stubs: { Select: SelectStub, Button: ButtonStub } },
    });

describe("Capacity Schedule oldal", () => {
    it("tervezési jogosultság nélkül nem jeleníti meg a generálási vezérlőket", () => {
        const wrapper = mountPage(false);
        expect(wrapper.findComponent(SelectStub).exists()).toBe(false);
        expect(wrapper.findComponent(ButtonStub).exists()).toBe(false);
    });

    it("jogosultsággal, kiválasztott rendelésre indítja a generálást", async () => {
        const wrapper = mountPage(true);
        const select = wrapper.findComponent(SelectStub);
        const button = wrapper.findComponent(ButtonStub);
        expect(button.props("disabled")).toBe(true);

        await select.vm.$emit("update:modelValue", 4);
        expect(button.props("disabled")).toBe(false);
        await button.trigger("click");

        expect(inertiaRouter.post).toHaveBeenCalledWith(
            "/admin/capacity/schedule",
            { production_order_id: 4 },
        );
    });
});
