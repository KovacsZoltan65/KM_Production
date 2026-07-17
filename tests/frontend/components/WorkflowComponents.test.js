import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import DocumentVersionHistory from "@/Components/DocumentVersionHistory.vue";
import MaterialUsageForm from "@/Components/MaterialUsageForm.vue";
import ProductionTaskActions from "@/Components/ProductionTaskActions.vue";
import QualityCheckForm from "@/Components/QualityCheckForm.vue";
import { inertiaRouter } from "../mocks/inertia.js";
import { inertiaPage } from "../mocks/inertia.js";
import { makeAuthPageProps } from "../fixtures/domain.js";

const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "loading", "size"],
    emits: ["click"],
    template: "<button @click='$emit(\"click\")'>{{ label }}</button>",
});

describe("ProductionTaskActions", () => {
    it("ready feladatnál a start műveletet teszi elérhetővé", async () => {
        const wrapper = shallowMount(ProductionTaskActions, {
            props: { task: { id: 5, status: "ready" }, dense: true },
            global: { stubs: { Button: ButtonStub } },
        });

        expect(wrapper.findComponent(ButtonStub).props("size")).toBe("small");
        await wrapper.findComponent(ButtonStub).trigger("click");
        expect(inertiaRouter.patch).toHaveBeenCalledWith(
            "/admin/production-tasks/5/start",
        );
    });

    it("folyamatban lévő feladatnál a finish műveletet indítja", async () => {
        const wrapper = shallowMount(ProductionTaskActions, {
            props: { task: { id: 6, status: "in_progress" } },
            global: { stubs: { Button: ButtonStub } },
        });

        await wrapper.findComponent(ButtonStub).trigger("click");
        expect(inertiaRouter.patch).toHaveBeenCalledWith(
            "/admin/production-tasks/6/finish",
        );
    });

    it("nem műveletezhető státusznál nem mutat gombot", () => {
        const wrapper = shallowMount(ProductionTaskActions, {
            props: { task: { id: 7, status: "finished" } },
            global: { stubs: { Button: ButtonStub } },
        });

        expect(wrapper.findComponent(ButtonStub).exists()).toBe(false);
    });
});

describe("DocumentVersionHistory", () => {
    it("csak a nem aktuális verziót engedi aktuálissá tenni", async () => {
        inertiaPage.props = makeAuthPageProps({
            auth: { permissions: ["documents.version"], roles: [] },
        });
        const wrapper = shallowMount(DocumentVersionHistory, {
            props: {
                versions: [
                    { id: 1, version: 1, is_current: false, uploader: null },
                    { id: 2, version: 2, is_current: true, uploader: null },
                ],
            },
            global: { stubs: { Button: ButtonStub, Link: true } },
        });

        expect(wrapper.findAllComponents(ButtonStub)).toHaveLength(1);
        await wrapper.findComponent(ButtonStub).trigger("click");
        expect(inertiaRouter.patch).toHaveBeenCalledWith(
            "/admin/documents/1/make-current",
            {},
            { preserveScroll: true },
        );
    });
});

describe("QualityCheckForm", () => {
    it("a minőségellenőrzést a feladat route-jára küldi és siker után a jegyzetet reseteli", async () => {
        const wrapper = shallowMount(QualityCheckForm, {
            props: {
                productionTask: { id: 8 },
                employeeOptions: [],
                qualityResultOptions: [],
            },
            global: { stubs: { Button: ButtonStub } },
        });

        await wrapper.find("form").trigger("submit");
        expect(wrapper.vm.form.post).toHaveBeenCalledWith(
            "/admin/production-tasks/8/quality-checks",
            expect.objectContaining({ preserveScroll: true }),
        );
        wrapper.vm.form.post.mock.calls[0][1].onSuccess();
        expect(wrapper.vm.form.reset).toHaveBeenCalledWith("notes");
    });

    it("a feldolgozási állapotot átadja a mentés gombnak", async () => {
        const wrapper = shallowMount(QualityCheckForm, {
            props: {
                productionTask: { id: 8 },
                employeeOptions: [],
                qualityResultOptions: [],
            },
            global: { stubs: { Button: ButtonStub } },
        });
        wrapper.vm.form.processing = true;
        await nextTick();

        expect(wrapper.findComponent(ButtonStub).props("loading")).toBe(true);
    });
});

describe("MaterialUsageForm", () => {
    it("cikkválasztáskor átveszi annak mértékegységét", async () => {
        const wrapper = shallowMount(MaterialUsageForm, {
            props: {
                productionTask: { id: 9 },
                itemOptions: [{ id: 3, label: "Lemez", unit: "kg" }],
                locationOptions: [],
            },
        });

        wrapper.vm.form.item_id = 3;
        await nextTick();

        expect(wrapper.vm.form.unit).toBe("kg");
    });

    it("anyagfelhasználást a feladat route-jára küld és siker után resetel", async () => {
        const wrapper = shallowMount(MaterialUsageForm, {
            props: {
                productionTask: { id: 9 },
                itemOptions: [],
                locationOptions: [],
            },
        });

        await wrapper.find("form").trigger("submit");
        expect(wrapper.vm.form.post).toHaveBeenCalledWith(
            "/admin/production-tasks/9/materials",
            expect.objectContaining({ preserveScroll: true }),
        );
        wrapper.vm.form.post.mock.calls[0][1].onSuccess();
        expect(wrapper.vm.form.reset).toHaveBeenCalledOnce();
    });
});
