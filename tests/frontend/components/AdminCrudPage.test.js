import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import AdminCrudPage from "@/Components/Admin/AdminCrudPage.vue";
import { makePagination, makeRecord } from "../fixtures/domain.js";
import { inertiaRouter } from "../mocks/inertia.js";

const serviceMocks = vi.hoisted(() => ({
    confirm: { require: vi.fn() },
    toast: { add: vi.fn() },
}));

vi.mock("primevue/useconfirm", () => ({
    useConfirm: () => serviceMocks.confirm,
}));
vi.mock("primevue/usetoast", () => ({
    useToast: () => serviceMocks.toast,
}));

const AdminLayoutStub = defineComponent({
    name: "AdminLayout",
    template: "<main><slot /></main>",
});
const AdminPageHeaderStub = defineComponent({
    name: "AdminPageHeader",
    props: ["title", "subtitle", "createLabel", "canCreate"],
    emits: ["create"],
    template:
        "<header><h1>{{ title }}</h1><button v-if='canCreate' data-test='create' @click='$emit(\"create\")'>{{ createLabel }}</button></header>",
});
const DialogStub = defineComponent({
    name: "Dialog",
    props: ["visible", "header"],
    emits: ["update:visible"],
    template: "<section v-if='visible' data-test='dialog'><h2>{{ header }}</h2><slot /></section>",
});
const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "type", "disabled"],
    emits: ["click"],
    template:
        "<button :type='type || \"button\"' :disabled='disabled' @click='$emit(\"click\")'>{{ label }}</button>",
});
const AdminCrudFieldStub = defineComponent({
    name: "AdminCrudField",
    props: ["modelValue", "field", "error", "options"],
    emits: ["update:modelValue"],
    template: "<label>{{ field.name }}<span v-if='error'>{{ error }}</span></label>",
});
const DataTableStub = defineComponent({
    name: "DataTable",
    props: [
        "value",
        "rows",
        "first",
        "totalRecords",
        "sortField",
        "sortOrder",
    ],
    emits: ["page", "sort"],
    template: "<div data-test='table'><slot /></div>",
});

const baseProps = () => ({
    title: "Cikkek",
    routeName: "admin.items",
    records: makePagination([makeRecord()]),
    filters: {},
    columns: [{ field: "name", header: "Név" }],
    fields: [
        { name: "name", label: "Név", type: "text", required: true },
        { name: "quantity", label: "Mennyiség", type: "number", default: 0 },
        { name: "active", label: "Aktív", type: "checkbox" },
    ],
});

const mountPage = (overrides = {}) =>
    shallowMount(AdminCrudPage, {
        props: { ...baseProps(), ...overrides },
        global: {
            stubs: {
                AdminLayout: AdminLayoutStub,
                AdminPageHeader: AdminPageHeaderStub,
                AdminCrudField: AdminCrudFieldStub,
                AdminSearchBar: true,
                AdminActionButtons: true,
                AdminStatusBadge: true,
                DataTable: DataTableStub,
                Column: true,
                Dialog: DialogStub,
                Button: ButtonStub,
                Toast: true,
                ConfirmDialog: true,
                Head: true,
            },
        },
    });

describe("AdminCrudPage", () => {
    beforeEach(() => {
        serviceMocks.confirm.require.mockReset();
        serviceMocks.toast.add.mockReset();
    });

    it("megjeleníti az oldal címét és az írható létrehozási műveletet", () => {
        const wrapper = mountPage();
        const header = wrapper.findComponent(AdminPageHeaderStub);

        expect(header.props()).toMatchObject({
            title: "Cikkek",
            canCreate: true,
            createLabel: "actions.create",
        });
    });

    it("a kapott rekordokat és pagination metát átadja a táblázatnak", () => {
        const records = makePagination([makeRecord({ id: 4, name: "Csavar" })], {
            current_page: 2,
            per_page: 25,
            total: 31,
        });
        const table = mountPage({ records }).findComponent(DataTableStub);

        expect(table.props()).toMatchObject({
            value: records.data,
            rows: 25,
            first: 25,
            totalRecords: 31,
        });
    });

    it("üres rekordlistát stabil üres táblázatértékként kezel", () => {
        const table = mountPage({
            records: makePagination([]),
        }).findComponent(DataTableStub);

        expect(table.props("value")).toEqual([]);
    });

    it("readOnly módban elrejti a létrehozást", () => {
        const wrapper = mountPage({ readOnly: true });

        expect(
            wrapper.findComponent(AdminPageHeaderStub).props("canCreate"),
        ).toBe(false);
        expect(wrapper.find("[data-test='create']").exists()).toBe(false);
    });

    it("létrehozáskor alapértékekkel nyitja meg a modalt", async () => {
        const wrapper = mountPage();

        await wrapper.find("[data-test='create']").trigger("click");

        expect(wrapper.find("[data-test='dialog']").exists()).toBe(true);
        expect(
            wrapper
                .findAllComponents(AdminCrudFieldStub)
                .map((field) => field.props("modelValue")),
        ).toEqual([null, 0, false]);
    });

    it("szerkesztéskor a kiválasztott rekord adataival tölti fel a formot", async () => {
        const wrapper = mountPage();

        wrapper.vm.openEdit({ id: 7, name: "Alumínium", quantity: 4, active: true });
        await nextTick();

        expect(
            wrapper
                .findAllComponents(AdminCrudFieldStub)
                .map((field) => field.props("modelValue")),
        ).toEqual(["Alumínium", 4, true]);
    });

    it("létrehozáskor POST műveletet küld a form payloadjával", async () => {
        const wrapper = mountPage();
        wrapper.vm.openCreate();
        await nextTick();
        wrapper.vm.form.name = "Új cikk";

        wrapper.vm.submit();

        expect(inertiaRouter.post).toHaveBeenCalledWith(
            "/admin/items",
            { name: "Új cikk", quantity: 0, active: false },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it("szerkesztéskor PUT műveletet és a rekordazonosítót használja", async () => {
        const wrapper = mountPage();
        wrapper.vm.openEdit({ id: 7, name: "Régi", quantity: 1, active: true });
        await nextTick();
        wrapper.vm.form.name = "Módosított";

        wrapper.vm.submit();

        expect(inertiaRouter.put).toHaveBeenCalledWith(
            "/admin/items/7",
            expect.objectContaining({ name: "Módosított" }),
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it("backend validációs hibát a megfelelő mezőnek ad át", async () => {
        const wrapper = mountPage();
        wrapper.vm.openCreate();
        wrapper.vm.submit();
        inertiaRouter.post.mock.calls[0][2].onError({ name: "Kötelező mező" });
        await nextTick();

        expect(
            wrapper.findAllComponents(AdminCrudFieldStub)[0].props("error"),
        ).toBe("Kötelező mező");
    });

    it("sikeres mentés után bezárja és alaphelyzetbe állítja a formot", async () => {
        const wrapper = mountPage();
        wrapper.vm.openCreate();
        wrapper.vm.form.name = "Mentendő";
        wrapper.vm.submit();

        inertiaRouter.post.mock.calls[0][2].onSuccess();
        await nextTick();

        expect(wrapper.find("[data-test='dialog']").exists()).toBe(false);
        expect(wrapper.vm.form.name).toBeNull();
    });

    it("törlés előtt megerősítést kér, majd a megfelelő rekordot törli", () => {
        const wrapper = mountPage();
        wrapper.vm.destroyRecord({ id: 9 });

        expect(serviceMocks.confirm.require).toHaveBeenCalledOnce();
        serviceMocks.confirm.require.mock.calls[0][0].accept();
        expect(inertiaRouter.delete).toHaveBeenCalledWith("/admin/items/9", {
            preserveScroll: true,
        });
    });

    it("lapozáskor megtartja a keresést és a szerveroldali rendezést", () => {
        const wrapper = mountPage({
            filters: { search: "csavar", sort: "name", direction: "desc" },
        });

        wrapper.vm.onPage({ page: 2, rows: 25 });

        expect(inertiaRouter.get).toHaveBeenCalledWith(
            "/admin/items",
            {
                search: "csavar",
                sort: "name",
                direction: "desc",
                per_page: 25,
                page: 3,
            },
            { preserveState: true, replace: true },
        );
    });

    it("rendezési eseménynél az első oldalra tölt újra", () => {
        const wrapper = mountPage();

        wrapper.vm.onSort({ sortField: "name", sortOrder: -1 });

        expect(inertiaRouter.get).toHaveBeenCalledWith(
            "/admin/items",
            expect.objectContaining({
                sort: "name",
                direction: "desc",
                page: 1,
            }),
            { preserveState: true, replace: true },
        );
    });

    it("azonos layoutGroup numerikus mezőket legfeljebb hármas sorokba rendez", async () => {
        const wrapper = mountPage({
            fields: [
                { name: "min", type: "number", layoutGroup: "limits" },
                { name: "target", type: "number", layoutGroup: "limits" },
                { name: "max", type: "number", layoutGroup: "limits" },
                { name: "reserve", type: "number", layoutGroup: "limits" },
                { name: "description", type: "textarea" },
            ],
        });
        wrapper.vm.openCreate();
        await nextTick();

        const groupedRows = wrapper.findAll("[data-layout-group='limits']");
        expect(groupedRows).toHaveLength(2);
        expect(groupedRows[0].classes()).toContain("lg:grid-cols-3");
        expect(groupedRows[0].findAllComponents(AdminCrudFieldStub)).toHaveLength(3);
        expect(groupedRows[1].findAllComponents(AdminCrudFieldStub)).toHaveLength(1);
    });
});
