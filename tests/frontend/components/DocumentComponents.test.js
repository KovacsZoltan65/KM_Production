import { defineComponent } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import DocumentPreviewCard from "@/Components/DocumentPreviewCard.vue";
import DocumentStatusBadge from "@/Components/DocumentStatusBadge.vue";
import DocumentVersionHistory from "@/Components/DocumentVersionHistory.vue";
import DocumentShow from "@/Pages/Admin/Documents/Show.vue";
import {
    makeAuthPageProps,
    makeDocument,
    makeDocumentVersion,
} from "../fixtures/domain.js";
import { inertiaPage, inertiaRouter } from "../mocks/inertia.js";

const services = vi.hoisted(() => ({
    confirm: { require: vi.fn() },
    toast: { add: vi.fn() },
}));
vi.mock("primevue/useconfirm", () => ({ useConfirm: () => services.confirm }));
vi.mock("primevue/usetoast", () => ({ useToast: () => services.toast }));

const TagStub = defineComponent({
    name: "Tag",
    props: ["value", "severity"],
    template: "<span>{{ value }}</span>",
});
const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "loading"],
    emits: ["click"],
    template: "<button @click='$emit(\"click\")'>{{ label }}</button>",
});
const LinkStub = defineComponent({
    name: "Link",
    props: ["href"],
    template: "<a :href='href'><slot /></a>",
});
const PassthroughStub = defineComponent({ template: "<div><slot /></div>" });

const mountShow = (permissions, document = makeDocument()) => {
    inertiaPage.props = makeAuthPageProps({
        auth: { user: null, permissions, roles: [] },
    });
    return shallowMount(DocumentShow, {
        props: { document, versions: [makeDocumentVersion()] },
        global: {
            stubs: {
                AdminLayout: PassthroughStub,
                Button: ButtonStub,
                Link: LinkStub,
                Head: true,
                Toast: true,
                ConfirmDialog: true,
                DocumentPreviewCard: true,
                DocumentVersionHistory: true,
            },
        },
    });
};

describe("DocumentPreviewCard", () => {
    it.each([
        [2048, "2.0 KB"],
        [2 * 1024 * 1024, "2.00 MB"],
        [0, "-"],
        [null, "-"],
    ])("%s bájtot %s méretként jelenít meg", (fileSize, expected) => {
        const wrapper = shallowMount(DocumentPreviewCard, {
            props: { document: makeDocument({ file_size: fileSize }) },
        });
        expect(wrapper.text()).toContain(expected);
    });

    it("hiányzó nested és opcionális adatoknál helyettesítő értékeket használ", () => {
        const wrapper = shallowMount(DocumentPreviewCard, {
            props: {
                document: makeDocument({
                    uploader: null,
                    approver: null,
                    mime_type: null,
                    checksum: null,
                    created_at: null,
                }),
            },
        });
        expect(wrapper.text()).toContain("-");
        expect(wrapper.exists()).toBe(true);
    });
});

describe("DocumentStatusBadge", () => {
    it("aktuális és jóváhagyott dokumentumhoz megfelelő státuszokat ad", () => {
        const wrapper = shallowMount(DocumentStatusBadge, {
            props: { document: { is_current: true, approved: true } },
            global: { stubs: { Tag: TagStub } },
        });
        expect(wrapper.findAllComponents(TagStub).map((tag) => tag.props())).toEqual([
            expect.objectContaining({ value: "status.current", severity: "success" }),
            expect.objectContaining({ value: "status.approved", severity: "info" }),
        ]);
    });

    it("archivált és függő dokumentumot is helyesen jelöl", () => {
        const wrapper = shallowMount(DocumentStatusBadge, {
            props: { document: { is_current: false, approved: false } },
            global: { stubs: { Tag: TagStub } },
        });
        expect(wrapper.findAllComponents(TagStub).map((tag) => tag.props())).toEqual([
            expect.objectContaining({ value: "status.archived", severity: "secondary" }),
            expect.objectContaining({ value: "status.pending", severity: "warn" }),
        ]);
    });
});

describe("dokumentumműveletek", () => {
    beforeEach(() => services.confirm.require.mockReset());

    it("csak view permission mellett elrejti a módosító és letöltési actionöket", () => {
        const wrapper = mountShow(["documents.view"]);
        expect(wrapper.findAllComponents(ButtonStub)).toHaveLength(0);
        expect(wrapper.find("a[href='/admin/documents/1/download']").exists()).toBe(false);
    });

    it("a kapott permissionök alapján megjeleníti az actionöket", () => {
        const wrapper = mountShow([
            "documents.update",
            "documents.delete",
            "documents.download",
            "documents.approve",
            "documents.version",
        ], makeDocument({ is_current: false }));
        const labels = wrapper.findAllComponents(ButtonStub).map((button) => button.props("label"));
        expect(labels).toEqual(expect.arrayContaining([
            "actions.approve",
            "actions.make_current",
            "actions.delete",
            "actions.save",
        ]));
        expect(wrapper.find("a[href='/admin/documents/1/download']").exists()).toBe(true);
    });

    it("jóváhagyás és verzióváltás a megfelelő PATCH route-ot használja", async () => {
        const wrapper = mountShow(["documents.approve", "documents.version"], makeDocument({ is_current: false }));
        const buttons = wrapper.findAllComponents(ButtonStub);
        await buttons.find((button) => button.props("label") === "actions.approve").trigger("click");
        await buttons.find((button) => button.props("label") === "actions.make_current").trigger("click");
        expect(inertiaRouter.patch).toHaveBeenNthCalledWith(1, "/admin/documents/1/approve", {}, { preserveScroll: true });
        expect(inertiaRouter.patch).toHaveBeenNthCalledWith(2, "/admin/documents/1/make-current", {}, { preserveScroll: true });
    });

    it("törlés előtt megerősítést kér", async () => {
        const wrapper = mountShow(["documents.delete"]);
        await wrapper.findComponent(ButtonStub).trigger("click");
        expect(services.confirm.require).toHaveBeenCalledOnce();
        expect(inertiaRouter.delete).not.toHaveBeenCalled();
        services.confirm.require.mock.calls[0][0].accept();
        expect(inertiaRouter.delete).toHaveBeenCalledWith("/admin/documents/1");
    });

    it("verzióelőzményben permission nélkül elrejti az aktuálissá tételt", () => {
        inertiaPage.props = makeAuthPageProps({ auth: { permissions: [], roles: [] } });
        const wrapper = shallowMount(DocumentVersionHistory, {
            props: { versions: [makeDocumentVersion()] },
            global: { stubs: { Button: ButtonStub, Link: LinkStub } },
        });
        expect(wrapper.findComponent(ButtonStub).exists()).toBe(false);
    });
});
