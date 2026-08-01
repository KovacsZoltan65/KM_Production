import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import AdminCrudField from "@/Components/Admin/AdminCrudField.vue";

const modelStub = (name, template, props = []) =>
    defineComponent({
        name,
        inheritAttrs: false,
        props: ["modelValue", "disabled", "invalid", "required", ...props],
        emits: ["update:modelValue"],
        template,
    });

const InputTextStub = modelStub(
    "InputText",
    '<input data-control="input-text" v-bind="$attrs" />',
    ["type", "min", "max", "step"],
);
const TextareaStub = modelStub(
    "Textarea",
    '<textarea data-control="textarea" />',
);
const SelectStub = modelStub("Select", '<select data-control="select" />', [
    "options",
]);
const MultiSelectStub = modelStub(
    "MultiSelect",
    '<div data-control="multiselect" />',
    ["options"],
);
const UnitSelectStub = modelStub(
    "UnitSelect",
    '<div data-control="unit-select" />',
);
const PasswordStub = defineComponent({
    name: "Password",
    props: {
        modelValue: String,
        inputId: String,
        feedback: Boolean,
        toggleMask: Boolean,
        disabled: Boolean,
        invalid: Boolean,
        required: Boolean,
    },
    emits: ["update:modelValue"],
    template: '<input data-control="password" />',
});
const DatePickerStub = modelStub(
    "DatePicker",
    '<input data-control="date-picker" />',
    ["inputId", "updateModelType", "dateFormat"],
);
const CheckboxStub = defineComponent({
    name: "Checkbox",
    inheritAttrs: false,
    props: {
        modelValue: Boolean,
        inputId: String,
        binary: Boolean,
        disabled: Boolean,
        invalid: Boolean,
        required: Boolean,
    },
    emits: ["update:modelValue"],
    template:
        '<button data-control="checkbox" v-bind="$attrs" @click="$emit(\'update:modelValue\', !modelValue)" />',
});
const IftaLabelStub = defineComponent({
    name: "IftaLabel",
    inheritAttrs: false,
    props: ["for"],
    template: '<label :for="$props.for"><slot /></label>',
});
const IconFieldStub = defineComponent({
    name: "IconField",
    template: '<div data-control="icon-field"><slot /></div>',
});
const InputIconStub = defineComponent({
    name: "InputIcon",
    inheritAttrs: false,
    template: '<i data-control="input-icon" v-bind="$attrs" />',
});

const stubs = {
    InputText: InputTextStub,
    Textarea: TextareaStub,
    Select: SelectStub,
    MultiSelect: MultiSelectStub,
    UnitSelect: UnitSelectStub,
    Password: PasswordStub,
    DatePicker: DatePickerStub,
    Checkbox: CheckboxStub,
    IftaLabel: IftaLabelStub,
    IconField: IconFieldStub,
    InputIcon: InputIconStub,
};

const mountField = (field, extraProps = {}, slots = {}) =>
    shallowMount(AdminCrudField, {
        props: { field, ...extraProps },
        slots,
        global: { stubs },
    });

describe("AdminCrudField", () => {
    it.each([
        ["text", "input-text"],
        ["number", "input-text"],
        ["textarea", "textarea"],
        ["select", "select"],
        ["multiselect", "multiselect"],
        ["checkbox", "checkbox"],
        ["unit", "unit-select"],
    ])("%s mezőhöz a megfelelő vezérlőt rendereli", (type, control) => {
        const wrapper = mountField({ name: "value", label: "Érték", type });

        expect(wrapper.find(`[data-control='${control}']`).exists()).toBe(true);
    });

    it("ikon nélküli szövegmezőnél megtartja a modellt és az action slotot", async () => {
        const wrapper = mountField(
            { name: "code", label: "Kód", type: "text" },
            { modelValue: "EMP-001" },
            { action: '<button data-test="field-action">Generálás</button>' },
        );
        const input = wrapper.findComponent(InputTextStub);

        expect(input.props("modelValue")).toBe("EMP-001");
        expect(wrapper.findComponent(InputIconStub).exists()).toBe(false);
        expect(wrapper.get("[data-test='field-action']").text()).toBe(
            "Generálás",
        );

        await input.vm.$emit("update:modelValue", "EMP-002");
        expect(wrapper.emitted("update:modelValue")).toEqual([["EMP-002"]]);
    });

    it("ikonos szövegmezőnél megtartja az input szerződését", async () => {
        const wrapper = mountField(
            { name: "email", label: "E-mail", type: "email", icon: "envelope" },
            { modelValue: "old@example.test" },
        );
        const input = wrapper.findComponent(InputTextStub);

        expect(wrapper.findComponent(IconFieldStub).exists()).toBe(true);
        expect(wrapper.findComponent(InputIconStub).classes()).toEqual(
            expect.arrayContaining(["pi", "pi-envelope"]),
        );
        expect(input.props("modelValue")).toBe("old@example.test");

        await input.vm.$emit("update:modelValue", "new@example.test");
        expect(wrapper.emitted("update:modelValue")).toEqual([
            ["new@example.test"],
        ]);
    });

    it("a jelszómező maszkkapcsolót használ visszajelzési panel nélkül", async () => {
        const wrapper = mountField(
            { name: "password", label: "Jelszó", type: "password" },
            { modelValue: "secret" },
        );
        const password = wrapper.findComponent(PasswordStub);

        expect(password.props()).toMatchObject({
            inputId: "password",
            modelValue: "secret",
            toggleMask: true,
            feedback: false,
        });

        await password.vm.$emit("update:modelValue", "replacement");
        expect(wrapper.emitted("update:modelValue")).toEqual([["replacement"]]);
    });

    it("a dátummező YYYY-MM-DD string modellt és invalid állapotot használ", async () => {
        const wrapper = mountField(
            { name: "hired_at", label: "Belépés", type: "date" },
            { modelValue: "2026-08-01", error: "Hibás dátum" },
        );
        const datePicker = wrapper.findComponent(DatePickerStub);

        expect(datePicker.props()).toMatchObject({
            inputId: "hired_at",
            modelValue: "2026-08-01",
            updateModelType: "string",
            dateFormat: "yy-mm-dd",
            invalid: true,
        });

        await datePicker.vm.$emit("update:modelValue", "2026-08-02");
        expect(wrapper.emitted("update:modelValue")).toEqual([["2026-08-02"]]);
    });

    it("a checkbox mindkét boolean irányt, labelt és mezőállapotot kezeli", async () => {
        const field = {
            name: "is_active",
            label: "Aktív",
            type: "checkbox",
            required: true,
            disabled: true,
        };
        const wrapper = mountField(field, { modelValue: true });
        const checkbox = wrapper.findComponent(CheckboxStub);
        const labels = wrapper.findAllComponents(IftaLabelStub);

        expect(checkbox.props()).toMatchObject({
            modelValue: true,
            inputId: "is_active",
            binary: true,
            required: true,
            disabled: true,
        });
        expect(checkbox.attributes("aria-required")).toBe("true");
        expect(labels.at(-1).props("for")).toBe("is_active");
        expect(labels.at(-1).text()).toContain("Aktív");

        await checkbox.trigger("click");
        expect(wrapper.emitted("update:modelValue")).toEqual([[false]]);

        await wrapper.setProps({ modelValue: false });
        await nextTick();
        await checkbox.trigger("click");
        expect(wrapper.emitted("update:modelValue")).toEqual([[false], [true]]);
    });

    it("csak required mezőnél jelenít meg képernyőolvasóktól rejtett csillagot", () => {
        const required = mountField({
            name: "quantity",
            label: "Mennyiség",
            type: "number",
            required: true,
        });
        const optional = mountField({
            name: "notes",
            label: "Megjegyzés",
            type: "text",
        });

        expect(required.text()).toContain("Mennyiség");
        expect(required.get("span[aria-hidden='true']").text()).toBe("*");
        expect(
            required.findComponent(InputTextStub).attributes("aria-required"),
        ).toBe("true");
        expect(optional.find("span[aria-hidden='true']").exists()).toBe(false);
    });

    it("a validációs hibát megjeleníti és invalid mezőállapotot ad át", () => {
        const wrapper = mountField(
            {
                name: "quantity",
                label: "Mennyiség",
                type: "number",
                disabled: true,
                min: 0,
                max: 10,
                step: 0.5,
            },
            { error: "Hibás mennyiség" },
        );
        const input = wrapper.findComponent(InputTextStub);

        expect(wrapper.text()).toContain("Hibás mennyiség");
        expect(input.props()).toMatchObject({
            type: "number",
            disabled: true,
            invalid: true,
            min: 0,
            max: 10,
            step: 0.5,
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
