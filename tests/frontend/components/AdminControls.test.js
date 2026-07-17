import { defineComponent } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import AdminActionButtons from "@/Components/Admin/AdminActionButtons.vue";
import AdminSearchBar from "@/Components/Admin/AdminSearchBar.vue";

const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "ariaLabel"],
    emits: ["click"],
    template:
        "<button :aria-label='ariaLabel' @click='$emit(\"click\")'>{{ label }}</button>",
});
const InputTextStub = defineComponent({
    name: "InputText",
    props: ["modelValue"],
    emits: ["update:modelValue"],
    template: "<input @keydown.enter='$emit(\"enter\")' />",
});
const SelectStub = defineComponent({
    name: "Select",
    props: ["modelValue", "options"],
    emits: ["update:modelValue"],
    template: "<div />",
});
const IconFieldStub = defineComponent({
    name: "IconField",
    template: "<div><slot /></div>",
});

const searchStubs = {
    Button: ButtonStub,
    InputText: InputTextStub,
    Select: SelectStub,
    IconField: IconFieldStub,
    InputIcon: true,
};

describe("AdminActionButtons", () => {
    it("alapértelmezetten elérhetővé teszi a szerkesztést és törlést", async () => {
        const wrapper = shallowMount(AdminActionButtons, {
            global: { stubs: { Button: ButtonStub } },
        });
        const buttons = wrapper.findAllComponents(ButtonStub);

        expect(buttons).toHaveLength(2);
        await buttons[0].trigger("click");
        await buttons[1].trigger("click");
        expect(wrapper.emitted("edit")).toHaveLength(1);
        expect(wrapper.emitted("delete")).toHaveLength(1);
    });

    it("jogosultság hiányában elrejti az érintett műveleteket", () => {
        const wrapper = shallowMount(AdminActionButtons, {
            props: { canEdit: false, canDelete: false },
            global: { stubs: { Button: ButtonStub } },
        });

        expect(wrapper.findAllComponents(ButtonStub)).toHaveLength(0);
    });
});

describe("AdminSearchBar", () => {
    it("továbbítja a keresőkifejezés és oldalméret modellfrissítéseit", async () => {
        const wrapper = shallowMount(AdminSearchBar, {
            props: { modelValue: "régi", perPage: 10 },
            global: { stubs: searchStubs },
        });
        const input = wrapper.findComponent(InputTextStub);
        const select = wrapper.findComponent(SelectStub);

        await input.vm.$emit("update:modelValue", "új");
        await select.vm.$emit("update:modelValue", 25);

        expect(wrapper.emitted("update:modelValue")).toEqual([["új"]]);
        expect(wrapper.emitted("update:perPage")).toEqual([[25]]);
    });

    it("Enter és gombkattintás esetén keresési eseményt küld", async () => {
        const wrapper = shallowMount(AdminSearchBar, {
            global: { stubs: searchStubs },
        });

        await wrapper.findComponent(InputTextStub).trigger("keydown.enter");
        await wrapper.findComponent(ButtonStub).trigger("click");

        expect(wrapper.emitted("search")).toHaveLength(2);
    });

    it("a támogatott oldalméreteket adja át", () => {
        const wrapper = shallowMount(AdminSearchBar, {
            global: { stubs: searchStubs },
        });

        expect(wrapper.findComponent(SelectStub).props("options")).toEqual([
            10, 25, 50, 100,
        ]);
    });
});
