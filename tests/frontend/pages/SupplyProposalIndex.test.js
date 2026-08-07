import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import SupplyProposalIndex from "@/Pages/Admin/SupplyProposals/Index.vue";
import SupplyProposalStatusBadge from "@/Pages/Admin/SupplyProposals/Partials/SupplyProposalStatusBadge.vue";
import { inertiaRouter } from "../mocks/inertia.js";

const confirmService = { require: vi.fn() };
vi.mock("primevue/useconfirm", () => ({ useConfirm: () => confirmService }));

const passthrough = (name) =>
    defineComponent({
        name,
        template: "<div><slot /></div>",
    });
const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "icon"],
    emits: ["click"],
    template:
        '<button v-bind="$attrs" @click="$emit(\'click\')">{{ label }}</button>',
});
const HeaderStub = defineComponent({
    name: "AdminPageHeader",
    props: ["title", "subtitle", "createLabel"],
    emits: ["create"],
    template:
        '<button data-test="create" @click="$emit(\'create\')">{{ createLabel }}</button>',
});
const DataTableStub = defineComponent({
    name: "DataTable",
    template: "<section><slot /></section>",
});
let activeRecord;
const ColumnStub = defineComponent({
    name: "Column",
    setup: () => ({ row: activeRecord }),
    template: '<div><slot name="body" :data="row" /></div>',
});
const SelectStub = defineComponent({
    name: "Select",
    props: ["modelValue", "options"],
    emits: ["update:modelValue"],
    template:
        "<select @change=\"$emit('update:modelValue', $event.target.value)\"><slot /></select>",
});
const InputStub = defineComponent({
    props: ["modelValue"],
    emits: ["update:modelValue"],
    template: "<input />",
});

const draft = {
    id: 10,
    strategy: "purchase",
    item_id: 1,
    supplier_id: 2,
    proposed_quantity: "100.000",
    unit: "kg",
    required_at: "2026-08-09T00:00:00.000000Z",
    proposed_supply_at: "2026-08-08T00:00:00.000000Z",
    status: "draft",
    reason_code: "material_shortage",
    notes: "Manual",
    item: {
        id: 1,
        item_number: "RAW-001",
        name: "Kémcső alapanyag",
        unit: "kg",
    },
    supplier: { id: 2, code: "SUP-001", name: "LaborPlast Kft." },
    creator: { id: 3, name: "Planner" },
};

const mountPage = (
    record = draft,
    abilities = { create: true, update: true, approve: true, cancel: true },
) => {
    activeRecord = record;

    return shallowMount(SupplyProposalIndex, {
        props: {
            records: {
                data: [record],
                current_page: 1,
                per_page: 10,
                total: 1,
            },
            filters: {},
            itemOptions: [
                { id: 1, unit: "kg", label: "RAW-001 - Kémcső alapanyag" },
            ],
            supplierOptionsByItem: {
                1: [{ id: 2, label: "SUP-001 - LaborPlast Kft." }],
            },
            strategyOptions: [{ value: "purchase", label: "Beszerzés" }],
            statusOptions: [{ value: "draft", label: "Piszkozat" }],
            abilities,
        },
        global: {
            stubs: {
                AdminLayout: passthrough("AdminLayout"),
                AdminPageHeader: HeaderStub,
                AdminSearchBar: passthrough("AdminSearchBar"),
                ConfirmDialog: true,
                DataTable: DataTableStub,
                Column: ColumnStub,
                Dialog: passthrough("Dialog"),
                Button: ButtonStub,
                Select: SelectStub,
                InputNumber: InputStub,
                InputText: InputStub,
                Textarea: InputStub,
                Head: true,
                SupplyProposalStatusBadge: passthrough(
                    "SupplyProposalStatusBadge",
                ),
            },
        },
    });
};

describe("Supply Proposals Index", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("renders the list and manual create entry point", () => {
        const wrapper = mountPage();
        expect(wrapper.find('[data-test="create"]').exists()).toBe(true);
        expect(wrapper.text()).toContain("RAW-001");
        expect(wrapper.text()).toContain("100.000 kg");
    });

    it("opens a create modal with manual proposal defaults", async () => {
        const wrapper = mountPage();
        await wrapper.find('[data-test="create"]').trigger("click");

        expect(wrapper.vm.dialogVisible).toBe(true);
        expect(wrapper.vm.editingRecord).toBeNull();
        expect(wrapper.vm.form).toMatchObject({
            strategy: "purchase",
            reason_code: "manual_planning",
        });
    });

    it("edits only a draft and normalizes its dates", () => {
        const wrapper = mountPage();
        wrapper.vm.openEdit(draft);

        expect(wrapper.vm.editingRecord.id).toBe(10);
        expect(wrapper.vm.form.required_at).toBe("2026-08-09");
    });

    it("keeps approved rejected and cancelled records read-only", () => {
        for (const status of ["approved", "rejected", "cancelled"]) {
            const wrapper = mountPage({ ...draft, status });
            wrapper.vm.openEdit({ ...draft, status });
            expect(wrapper.vm.editingRecord).toBeNull();
        }
    });

    it("offers only item-specific usable suppliers for purchase", async () => {
        const wrapper = mountPage();
        wrapper.vm.form.item_id = 1;
        await nextTick();
        expect(wrapper.vm.supplierOptions).toEqual([
            { id: 2, label: "SUP-001 - LaborPlast Kft." },
        ]);

        wrapper.vm.form.strategy = "transfer";
        await nextTick();
        expect(wrapper.vm.supplierOptions).toEqual([]);
        expect(wrapper.vm.form.supplier_id).toBeNull();
    });

    it("submits create and draft edit through their canonical routes", () => {
        const wrapper = mountPage();
        wrapper.vm.submit();
        expect(inertiaRouter.post).toHaveBeenCalledWith(
            "/admin/supply-proposals",
            expect.any(Object),
            expect.any(Object),
        );

        wrapper.vm.openEdit(draft);
        wrapper.vm.submit();
        expect(inertiaRouter.put).toHaveBeenCalledWith(
            "/admin/supply-proposals/10",
            expect.any(Object),
            expect.any(Object),
        );
    });

    it("shows validation errors returned by the backend", () => {
        const wrapper = mountPage();
        wrapper.vm.submit();
        const callbacks = inertiaRouter.post.mock.calls.at(-1)[2];
        callbacks.onError({ proposed_quantity: "Required" });
        expect(wrapper.vm.errors).toEqual({ proposed_quantity: "Required" });
    });

    it("requests lifecycle transitions only after confirmation", () => {
        const wrapper = mountPage();
        wrapper.vm.transition(draft, "propose");
        expect(confirmService.require).toHaveBeenCalled();

        confirmService.require.mock.calls[0][0].accept();
        expect(inertiaRouter.patch).toHaveBeenCalledWith(
            "/admin/supply-proposals/10/propose",
            {},
            { preserveScroll: true },
        );
    });

    it("renders status-specific and permission-aware action buttons", () => {
        expect(
            mountPage(draft).findAll("button[aria-label]").length,
        ).toBeGreaterThanOrEqual(3);
        expect(
            mountPage({ ...draft, status: "rejected" }).findAll(
                "button[aria-label]",
            ),
        ).toHaveLength(0);
        expect(
            mountPage(draft, {
                create: false,
                update: false,
                approve: false,
                cancel: false,
            }).findAll("button[aria-label]"),
        ).toHaveLength(0);
    });

    it("renders localized status badge contract", () => {
        const wrapper = shallowMount(SupplyProposalStatusBadge, {
            props: { status: "approved" },
            global: {
                stubs: {
                    Tag: defineComponent({
                        props: ["value", "severity"],
                        template:
                            '<span :data-severity="severity">{{ value }}</span>',
                    }),
                },
            },
        });
        expect(wrapper.find('[data-severity="success"]').exists()).toBe(true);
    });
});
