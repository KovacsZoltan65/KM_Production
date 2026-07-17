import { defineComponent, nextTick, ref } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import LocaleSelector from "@/Components/LocaleSelector.vue";
import TopbarLocaleSwitch from "@/Components/TopbarLocaleSwitch.vue";

const preferenceMock = vi.hoisted(() => ({
    availableLocales: null,
    locale: null,
    setLocale: vi.fn(),
}));

vi.mock("@/Composables/usePreferences", () => ({
    usePreferences: () => preferenceMock,
}));

const LocaleSelectorStub = defineComponent({
    name: "LocaleSelector",
    props: ["modelValue", "options", "placeholder"],
    emits: ["update:modelValue", "change"],
    template: "<div />",
});
const SelectStub = defineComponent({
    name: "Select",
    props: ["modelValue", "options", "placeholder"],
    emits: ["update:modelValue"],
    template: "<div />",
});

describe("TopbarLocaleSwitch", () => {
    beforeEach(() => {
        preferenceMock.availableLocales = ref([
            { value: "hu" },
            { value: "en" },
        ]);
        preferenceMock.locale = ref("hu");
        preferenceMock.setLocale.mockReset().mockResolvedValue(undefined);
    });

    it("megjeleníti a locale selectort lokalizált opciókkal", () => {
        const wrapper = shallowMount(TopbarLocaleSwitch, {
            global: { stubs: { LocaleSelector: LocaleSelectorStub } },
        });
        const selector = wrapper.findComponent(LocaleSelectorStub);

        expect(selector.props("modelValue")).toBe("hu");
        expect(selector.props("options")).toEqual([
            { value: "hu", label: "common.locales.hu" },
            { value: "en", label: "common.locales.en" },
        ]);
    });

    it("nyelvváltáskor a composable mentési műveletét hívja", async () => {
        const wrapper = shallowMount(TopbarLocaleSwitch, {
            global: { stubs: { LocaleSelector: LocaleSelectorStub } },
        });

        await wrapper.findComponent(LocaleSelectorStub).vm.$emit("change", "en");

        expect(preferenceMock.setLocale).toHaveBeenCalledWith("en");
    });

    it("külső locale változáskor frissíti a kiválasztott értéket", async () => {
        const wrapper = shallowMount(TopbarLocaleSwitch, {
            global: { stubs: { LocaleSelector: LocaleSelectorStub } },
        });

        preferenceMock.locale.value = "en";
        await nextTick();

        expect(
            wrapper.findComponent(LocaleSelectorStub).props("modelValue"),
        ).toBe("en");
    });
});

describe("LocaleSelector", () => {
    it("hiányzó címkéket fordítási kulccsal egészít ki", () => {
        const wrapper = shallowMount(LocaleSelector, {
            props: { options: [{ value: "hu" }, { value: "en", label: "EN" }] },
            global: { stubs: { Select: SelectStub } },
        });

        expect(wrapper.findComponent(SelectStub).props("options")).toEqual([
            { value: "hu", label: "common.locales.hu" },
            { value: "en", label: "EN" },
        ]);
    });

    it("választáskor modell- és change eseményt is küld", async () => {
        const wrapper = shallowMount(LocaleSelector, {
            global: { stubs: { Select: SelectStub } },
        });

        await wrapper.findComponent(SelectStub).vm.$emit(
            "update:modelValue",
            "en",
        );

        expect(wrapper.emitted("update:modelValue")).toEqual([["en"]]);
        expect(wrapper.emitted("change")).toEqual([["en"]]);
    });
});
