import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import DocumentIndex from "@/Pages/Admin/Documents/Index.vue";
import { makeAuthPageProps, makeDocument, makePagination } from "../fixtures/domain.js";
import { inertiaPage, inertiaRouter } from "../mocks/inertia.js";

vi.mock("primevue/usetoast", () => ({
    useToast: () => ({ add: vi.fn() }),
}));

const PassthroughStub = defineComponent({ template: "<div><slot /></div>" });
const HeaderStub = defineComponent({
    name: "AdminPageHeader",
    props: ["canCreate"],
    emits: ["create"],
    template: "<button v-if='canCreate' @click='$emit(\"create\")'>create</button>",
});
const DialogStub = defineComponent({
    name: "Dialog",
    props: ["visible"],
    template: "<div v-if='visible' data-test='upload-dialog'><slot /></div>",
});

const mountPage = (permissions = [], roles = []) => {
    inertiaPage.props = makeAuthPageProps({
        auth: { user: null, permissions, roles },
    });
    return shallowMount(DocumentIndex, {
        props: {
            records: makePagination([makeDocument()]),
            filters: {},
            documentTypeOptions: [],
            documentableTypeOptions: [],
        },
        global: {
            stubs: {
                AdminLayout: PassthroughStub,
                AdminPageHeader: HeaderStub,
                AdminSearchBar: true,
                DocumentUploadForm: true,
                DocumentStatusBadge: true,
                Dialog: DialogStub,
                DataTable: true,
                Column: true,
                Select: true,
                Toast: true,
                Head: true,
                Link: true,
            },
        },
    });
};

describe("Documents Index", () => {
    it("documents.create nélkül elrejti a feltöltési műveletet", () => {
        const wrapper = mountPage(["documents.view"]);
        expect(wrapper.findComponent(HeaderStub).props("canCreate")).toBe(false);
        expect(wrapper.find("button").exists()).toBe(false);
    });

    it("feltöltési permissionnel megnyitja a dokumentumform dialogját", async () => {
        const wrapper = mountPage(["documents.create"]);
        await wrapper.get("button").trigger("click");
        await nextTick();
        expect(wrapper.find("[data-test='upload-dialog']").exists()).toBe(true);
    });

    it("a letöltési permissiont és super-admin szerepet külön kezeli", () => {
        expect(mountPage(["documents.download"]).vm.canDownload).toBe(true);
        expect(mountPage([]).vm.canDownload).toBe(false);
        expect(mountPage([], ["super-admin"]).vm.canDownload).toBe(true);
    });

    it("szűréskor a dokumentum query szerződésével tölt újra", () => {
        const wrapper = mountPage(["documents.view"]);
        wrapper.vm.documentType = "work_instruction";
        wrapper.vm.approved = "0";

        wrapper.vm.reload(2);

        expect(inertiaRouter.get).toHaveBeenCalledWith(
            "/admin/documents",
            expect.objectContaining({
                document_type: "work_instruction",
                approved: "0",
                page: 2,
            }),
            { preserveState: true, replace: true },
        );
    });
});
