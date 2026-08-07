import { defineComponent } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import ItemSupplierIndex from "@/Pages/Admin/ItemSuppliers/Index.vue";
import { inertiaRouter } from "../mocks/inertia.js";

const services = { toast: { add: vi.fn() } };

vi.mock("primevue/usetoast", () => ({
    useToast: () => services.toast,
}));

const AdminCrudPageStub = defineComponent({
    name: "AdminCrudPage",
    props: [
        "titleKey",
        "subtitleKey",
        "routeName",
        "createLabelKey",
        "records",
        "filters",
        "columns",
        "fields",
        "options",
    ],
    template: "<section><slot name='header-actions' /></section>",
});

const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "loading", "disabled"],
    emits: ["click"],
    template:
        "<button :disabled='disabled' @click='$emit(\"click\")'>{{ label }}</button>",
});

const records = {
    data: [],
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
};

const mountPage = () =>
    shallowMount(ItemSupplierIndex, {
        props: {
            records,
            filters: {},
            itemOptions: [
                {
                    id: 1,
                    item_number: "RAW-001",
                    name: "Kémcső alapanyag",
                    unit: "kg",
                    label: "RAW-001 - Kémcső alapanyag",
                },
            ],
            supplierOptions: [
                {
                    id: 2,
                    code: "SUP-001",
                    name: "LaborPlast Kft.",
                    label: "SUP-001 - LaborPlast Kft.",
                },
            ],
        },
        global: {
            stubs: {
                AdminCrudPage: AdminCrudPageStub,
                Button: ButtonStub,
            },
        },
    });

describe("Procurement Sources Index", () => {
    beforeEach(() => services.toast.add.mockReset());

    it("a közös CRUD oldalt és a lokalizált üzleti címeket használja", () => {
        const crud = mountPage().findComponent(AdminCrudPageStub);

        expect(crud.props()).toMatchObject({
            titleKey: "procurement.sources.title",
            subtitleKey: "procurement.sources.subtitle",
            createLabelKey: "procurement.sources.create",
            routeName: "admin.item-suppliers",
        });
    });

    it("átadja az Item és Supplier option struktúrát a selectoroknak", () => {
        const crud = mountPage().findComponent(AdminCrudPageStub);

        expect(crud.props("options")).toMatchObject({
            itemOptions: [expect.objectContaining({ id: 1, unit: "kg" })],
            supplierOptions: [expect.objectContaining({ id: 2 })],
        });
        expect(
            crud.props("fields").find((field) => field.name === "item_id"),
        ).toMatchObject({ type: "select", options: "itemOptions" });
        expect(
            crud.props("fields").find((field) => field.name === "supplier_id"),
        ).toMatchObject({ type: "select", options: "supplierOptions" });
    });

    it("a numerikus és boolean üzleti mezőket megfelelő kontrollokkal adja át", () => {
        const fields = mountPage()
            .findComponent(AdminCrudPageStub)
            .props("fields");

        expect(
            fields.filter((field) => field.layoutGroup === "ordering"),
        ).toHaveLength(3);
        expect(
            fields.filter((field) => field.layoutGroup === "states"),
        ).toEqual([
            expect.objectContaining({ name: "is_preferred", type: "checkbox" }),
            expect.objectContaining({ name: "is_approved", type: "checkbox" }),
            expect.objectContaining({ name: "is_active", type: "checkbox" }),
        ]);
        expect(
            fields.find((field) => field.name === "conversion_factor"),
        ).toMatchObject({ type: "number", min: 0.000001, step: 0.000001 });
    });

    it("a listában minden előírt beszerzési feltételt megjelenít", () => {
        const columnFields = mountPage()
            .findComponent(AdminCrudPageStub)
            .props("columns")
            .map((column) => column.field);

        expect(columnFields).toEqual(
            expect.arrayContaining([
                "item.name",
                "supplier.name",
                "supplier_item_code",
                "purchase_unit",
                "minimum_order_quantity",
                "order_multiple",
                "lead_time_days",
                "unit_price",
                "priority",
                "is_preferred",
                "is_approved",
                "is_active",
            ]),
        );
    });

    it("frissítéskor csak a rekordlistát kéri újra", async () => {
        const wrapper = mountPage();

        await wrapper.findComponent(ButtonStub).trigger("click");

        expect(inertiaRouter.reload).toHaveBeenCalledWith(
            expect.objectContaining({
                only: ["records"],
                preserveState: true,
                preserveScroll: true,
            }),
        );
    });
});
