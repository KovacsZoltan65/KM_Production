import { Head } from "@inertiajs/vue3";
import { mount } from "@vue/test-utils";
import { defineComponent, nextTick, ref } from "vue";
import { describe, expect, it } from "vitest";
import { DEFAULT_APP_NAME, formatPageTitle } from "@/Utils/pageTitle";

const HeadHarness = defineComponent({
    components: { Head },
    setup() {
        const title = ref("");
        return { title };
    },
    template: `
        <Head :title="title" />
        <main><h1>{{ title || "Untitled" }}</h1></main>
    `,
});

describe("Inertia head management", () => {
    it("keeps the application title as the empty-title fallback", () => {
        expect(formatPageTitle()).toBe(DEFAULT_APP_NAME);
        expect(formatPageTitle("", "Factory MES")).toBe("Factory MES");
    });

    it("adds the configured application suffix exactly once", () => {
        expect(formatPageTitle("Dashboard", "Factory MES")).toBe(
            "Dashboard | Factory MES",
        );
        expect(formatPageTitle("Dashboard | Factory MES", "Factory MES")).toBe(
            "Dashboard | Factory MES",
        );
        expect(formatPageTitle("Factory MES", "Factory MES")).toBe(
            "Factory MES",
        );
        expect(formatPageTitle(null, "Factory MES")).toBe("Factory MES");
    });

    it("updates the visible page and document title during navigation", async () => {
        const wrapper = mount(HeadHarness);

        expect(wrapper.get("h1").text()).toBe("Untitled");
        expect(document.title).toBe("KM Production");

        wrapper.vm.title = "Dashboard";
        await nextTick();
        expect(wrapper.get("h1").text()).toBe("Dashboard");
        expect(document.title).toBe("Dashboard | KM Production");

        wrapper.vm.title = "Inventory";
        await nextTick();
        expect(wrapper.get("h1").text()).toBe("Inventory");
        expect(document.title).toBe("Inventory | KM Production");
    });
});
