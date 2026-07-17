import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import DocumentUploadForm from "@/Components/DocumentUploadForm.vue";

const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "loading", "type"],
    emits: ["click"],
    template:
        "<button :type='type || \"button\"' @click='$emit(\"click\")'>{{ label }}</button>",
});
const MessageStub = defineComponent({
    name: "Message",
    template: "<div data-test='error'><slot /></div>",
});

const mountForm = () =>
    shallowMount(DocumentUploadForm, {
        props: {
            documentTypeOptions: [
                { label: "Munkautasítás", value: "work_instruction" },
            ],
            documentableTypeOptions: [
                {
                    label: "Gyártási rendelés",
                    value: "production_order",
                    class: "ProductionOrder",
                },
            ],
        },
        global: { stubs: { Button: ButtonStub, Message: MessageStub } },
    });

const selectFile = async (wrapper, file) => {
    const input = wrapper.get("input[type='file']");
    Object.defineProperty(input.element, "files", {
        configurable: true,
        value: file ? [file] : [],
    });
    await input.trigger("change");
};

describe("DocumentUploadForm", () => {
    it("File objektum kiválasztásakor a fájlt változatlanul a formba teszi", async () => {
        const wrapper = mountForm();
        const file = new File(["PDF"], "utasitas.pdf", {
            type: "application/pdf",
        });

        await selectFile(wrapper, file);

        expect(wrapper.vm.form.file).toBe(file);
        expect(wrapper.vm.form.file.name).toBe("utasitas.pdf");
        expect(wrapper.vm.form.file.type).toBe("application/pdf");
    });

    it("fájlcsere esetén az új fájl lesz aktív", async () => {
        const wrapper = mountForm();
        await selectFile(wrapper, new File(["a"], "elso.txt"));
        const replacement = new File(["b"], "masodik.bin", {
            type: "application/octet-stream",
        });

        await selectFile(wrapper, replacement);

        expect(wrapper.vm.form.file).toBe(replacement);
    });

    it("üres fájlkiválasztás null állapotot eredményez", async () => {
        const wrapper = mountForm();
        await selectFile(wrapper, null);
        expect(wrapper.vm.form.file).toBeNull();
    });

    it("a teljes dokumentumformot a megfelelő route-ra küldi", async () => {
        const wrapper = mountForm();
        const file = new File(["PDF"], "utasitas.pdf", {
            type: "application/pdf",
        });
        await selectFile(wrapper, file);
        Object.assign(wrapper.vm.form, {
            document_type: "work_instruction",
            documentable_type: "production_order",
            documentable_id: 12,
            title: "Új utasítás",
            notes: "Megjegyzés",
        });

        await wrapper.get("form").trigger("submit");

        expect(wrapper.vm.form.post).toHaveBeenCalledWith(
            "/admin/documents",
            expect.objectContaining({
                forceFormData: true,
                preserveScroll: true,
            }),
        );
        expect(wrapper.vm.form.file).toBe(file);
        expect(wrapper.vm.form.documentable_id).toBe(12);
    });

    it.each([
        ["file", "A fájl túl nagy."],
        ["document_type", "A dokumentumtípus kötelező."],
        ["title", "A cím kötelező."],
        ["notes", "A megjegyzés hibás."],
    ])("a %s backend hibát a megfelelő mezőnél jeleníti meg", async (key, message) => {
        const wrapper = mountForm();
        wrapper.vm.form.errors[key] = message;
        await nextTick();

        expect(wrapper.text()).toContain(message);
    });

    it("processing állapotot átadja a feltöltés gombnak", async () => {
        const wrapper = mountForm();
        wrapper.vm.form.processing = true;
        await nextTick();

        expect(wrapper.findAllComponents(ButtonStub)[1].props("loading")).toBe(
            true,
        );
    });

    it("processing közben nem indít dupla feltöltést", async () => {
        const wrapper = mountForm();
        wrapper.vm.form.processing = true;

        await wrapper.get("form").trigger("submit");

        expect(wrapper.vm.form.post).not.toHaveBeenCalled();
    });

    it("siker után reseteli a formot és uploaded eseményt küld", async () => {
        const wrapper = mountForm();
        await wrapper.get("form").trigger("submit");

        wrapper.vm.form.post.mock.calls[0][1].onSuccess();

        expect(wrapper.vm.form.reset).toHaveBeenCalledOnce();
        expect(wrapper.emitted("uploaded")).toHaveLength(1);
    });

    it("a megszakítási gomb cancel eseményt küld", async () => {
        const wrapper = mountForm();
        await wrapper.findAllComponents(ButtonStub)[0].trigger("click");
        expect(wrapper.emitted("cancel")).toHaveLength(1);
    });
});
