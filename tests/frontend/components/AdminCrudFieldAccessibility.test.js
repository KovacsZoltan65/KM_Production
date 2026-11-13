import { defineComponent, nextTick } from "vue";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { mountWithApp } from "../helpers/mount.js";
import AdminCrudField from "@/Components/Admin/AdminCrudField.vue";

const wrappers = [];
// jsdom has no layout; PrimeVue binds label clicks only for visible elements.
beforeEach(() =>
    vi
        .spyOn(HTMLElement.prototype, "offsetParent", "get")
        .mockReturnValue(document.body),
);
afterEach(() => {
    for (const wrapper of wrappers.splice(0)) wrapper.unmount();
});
const render = (field, props = {}) => {
    const wrapper = mountWithApp(AdminCrudField, {
        attachTo: document.body,
        props: {
            field: {
                name: "shared",
                label: "Field label",
                options: ["first", "second"],
                ...field,
            },
            ...props,
        },
    });
    wrappers.push(wrapper);
    return wrapper;
};

describe("AdminCrudField semantic labels with real PrimeVue controls", () => {
    it.each([
        "text",
        "email",
        "number",
        "textarea",
        "password",
        "date",
        "checkbox",
        "select",
        "unit",
        "multiselect",
    ])("names the actual %s control exactly once", async (type) => {
        const wrapper = render(
            { type, required: true },
            { error: "Validation error" },
        );
        const labels = wrapper.findAll("label");
        expect(labels).toHaveLength(1);
        const label = labels[0].element;
        const target = document.getElementById(label.htmlFor);
        expect(target).not.toBeNull();
        expect(target.matches("input, textarea, [role='combobox']")).toBe(true);
        expect(wrapper.text()).toContain("Validation error");
        expect(
            label.querySelector("[aria-hidden='true']")?.textContent.trim(),
        ).toBe("*");
        if (["select", "unit"].includes(type)) {
            expect(target.getAttribute("role")).toBe("combobox");
            expect(target.getAttribute("aria-labelledby")).toBe(label.id);
            expect(wrapper.findAll(`[id="${label.htmlFor}"]`)).toHaveLength(1);
            await labels[0].trigger("click");
            expect(document.activeElement).toBe(target);
        } else {
            expect(label.control).toBe(target);
        }
    });

    it("keeps repeated field names unique and IDs stable across updates", async () => {
        const Parent = defineComponent({
            props: { error: String },
            components: { AdminCrudField },
            template:
                "<div><AdminCrudField :error=\"error\" :field=\"{name: 'value', label: 'First', type: 'text'}\"/><AdminCrudField :field=\"{name: 'value', label: 'Second', type: 'text'}\"/></div>",
        });
        const wrapper = mountWithApp(Parent, { attachTo: document.body });
        wrappers.push(wrapper);
        const fields = wrapper.findAllComponents(AdminCrudField);
        const ids = fields.map((field) => field.get("input").attributes("id"));
        expect(new Set(ids).size).toBe(2);
        await wrapper.setProps({ error: "Changed validation" });
        await nextTick();
        expect(
            fields.map((field) => field.get("input").attributes("id")),
        ).toEqual(ids);
        expect(
            wrapper
                .findAll("label")
                .every((label) => label.element.control !== null),
        ).toBe(true);
    });

    it("retains native input updates and disabled/invalid state", async () => {
        const wrapper = render(
            { type: "text", disabled: false },
            { error: "Invalid" },
        );
        await wrapper.get("input").setValue("updated");
        expect(wrapper.emitted("update:modelValue")).toEqual([["updated"]]);
        expect(wrapper.get("input").attributes("aria-invalid")).toBe("true");
        await wrapper.setProps({
            field: {
                name: "shared",
                label: "Field label",
                type: "text",
                disabled: true,
            },
        });
        expect(wrapper.get("input").element.disabled).toBe(true);
    });
});
