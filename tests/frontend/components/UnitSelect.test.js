import { defineComponent } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import UnitSelect from "@/Components/Admin/UnitSelect.vue";
import { UNIT_OPTIONS } from "@/Constants/units";

const SelectStub = defineComponent({
    name: "Select",
    props: {
        modelValue: { default: null },
        options: Array,
        placeholder: String,
        disabled: Boolean,
        invalid: Boolean,
        required: Boolean,
    },
    emits: ["update:modelValue"],
    template: "<div data-test='select' />",
});

const mountSelect = (props = {}) =>
    shallowMount(UnitSelect, {
        props,
        global: { stubs: { Select: SelectStub } },
    });

describe("UnitSelect", () => {
    it("a támogatott mértékegységeket változatlan értékekkel adja át", () => {
        const select = mountSelect().findComponent(SelectStub);

        expect(select.props("options")).toEqual(UNIT_OPTIONS);
        expect(select.props("options").map(({ value }) => value)).toEqual([
            "db",
            "kg",
            "m",
            "m2",
            "m3",
            "l",
            "ml",
            "mm",
            "cm",
        ]);
    });

    it("a jelenlegi értéket kiválasztja és változáskor modelleseményt küld", async () => {
        const wrapper = mountSelect({ modelValue: "kg" });
        const select = wrapper.findComponent(SelectStub);
        expect(select.props("modelValue")).toBe("kg");

        await select.vm.$emit("update:modelValue", "m");

        expect(wrapper.emitted("update:modelValue")).toEqual([["m"]]);
    });

    it("átadja a tiltott, hibás és kötelező állapotokat", () => {
        const select = mountSelect({
            disabled: true,
            invalid: true,
            required: true,
        }).findComponent(SelectStub);

        expect(select.props()).toMatchObject({
            disabled: true,
            invalid: true,
            required: true,
        });
    });

    it("egyedi placeholdert és lokalizált alapértelmezést kezel", () => {
        expect(
            mountSelect({ placeholder: "Mérték" })
                .findComponent(SelectStub)
                .props("placeholder"),
        ).toBe("Mérték");
        expect(
            mountSelect().findComponent(SelectStub).props("placeholder"),
        ).toBe("fields.unit");
    });

    it("null és ismeretlen értéknél is renderelhető marad", () => {
        expect(mountSelect({ modelValue: null }).exists()).toBe(true);
        expect(
            mountSelect({ modelValue: "ismeretlen" })
                .findComponent(SelectStub)
                .props("modelValue"),
        ).toBe("ismeretlen");
    });
});
