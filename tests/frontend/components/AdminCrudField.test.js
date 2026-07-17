import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import AdminCrudField from "@/Components/Admin/AdminCrudField.vue";

const mountField = (field, extraProps = {}) =>
    shallowMount(AdminCrudField, { props: { field, ...extraProps } });

describe("AdminCrudField", () => {
    it.each([
        ["text", "input-text-stub"],
        ["number", "input-text-stub"],
        ["textarea", "textarea-stub"],
        ["select", "select-stub"],
        ["multiselect", "multi-select-stub"],
        ["checkbox", "checkbox-stub"],
        ["unit", "unit-select-stub"],
    ])("%s mezőhöz a megfelelő vezérlőt rendereli", (type, selector) => {
        const wrapper = mountField({ name: "value", label: "Érték", type });
        expect(wrapper.find(selector).exists()).toBe(true);
    });

    it("a kötelező jelölést, hibát és mezőállapotokat megjeleníti", () => {
        const wrapper = mountField(
            {
                name: "quantity",
                label: "Mennyiség",
                type: "number",
                required: true,
                disabled: true,
                min: 0,
                max: 10,
                step: 0.5,
            },
            { error: "Hibás mennyiség" },
        );

        expect(wrapper.text()).toContain("Mennyiség *");
        expect(wrapper.text()).toContain("Hibás mennyiség");
        expect(wrapper.find("input-text-stub").attributes()).toMatchObject({
            type: "number",
            disabled: "true",
            invalid: "true",
            required: "true",
        });
    });

    it("névvel hivatkozott opciólistát a konfigurált mezőkre alakít", () => {
        const wrapper = mountField(
            {
                name: "employee_id",
                labelKey: "fields.employee",
                type: "select",
                options: "employees",
                optionLabel: "full_name",
                optionValue: "uuid",
            },
            {
                options: {
                    employees: [{ uuid: "e-1", full_name: "Minta Anna" }],
                },
            },
        );

        expect(wrapper.vm.optionItems).toEqual([
            { label: "Minta Anna", value: "e-1" },
        ]);
        expect(wrapper.text()).toContain("fields.employee");
    });

    it("primitív opciókat stabil label-value párokká alakít", () => {
        const wrapper = mountField({
            name: "status",
            label: "Állapot",
            type: "select",
            options: ["draft", "approved"],
        });

        expect(wrapper.vm.optionItems).toEqual([
            { label: "draft", value: "draft" },
            { label: "approved", value: "approved" },
        ]);
    });
});
