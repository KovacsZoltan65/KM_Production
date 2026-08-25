import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import PurchaseRequisitionShow from "@/Pages/Admin/PurchaseRequisitions/Show.vue";
import { inertiaRouter } from "../mocks/inertia.js";

const services = {
    toast: { add: vi.fn() },
    confirm: { require: vi.fn() },
};

vi.mock("primevue/usetoast", () => ({
    useToast: () => services.toast,
}));

vi.mock("primevue/useconfirm", () => ({
    useConfirm: () => services.confirm,
}));

const LayoutStub = defineComponent({
    name: "AdminLayout",
    template: "<main><slot /></main>",
});

const PassthroughStub = defineComponent({
    name: "PassthroughStub",
    template: "<section><slot /></section>",
});

const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "loading", "disabled"],
    emits: ["click"],
    template: `
        <button
            :disabled="disabled"
            :data-loading="String(Boolean(loading))"
            @click="$emit('click')"
        >{{ label }}</button>
    `,
});

const requisition = (status) => ({
    id: 42,
    requisition_number: "PR-TEST-0042",
    status,
    supplier_id: null,
    supplier: null,
    items: [],
});

const candidate = {
    supplier_id: 7,
    supplier_code: "SUP-7",
    supplier_name: "Supplier",
    sources: [
        {
            item_supplier_id: 11,
            item_id: 1,
            item_number: "MAT-1",
            item_name: "Material",
            preferred: true,
            priority: 1,
            lead_time_days: 5,
            unit_price: "1250.0000",
            currency: "HUF",
            purchase_unit: "bag",
            conversion_factor: "25.000000",
            minimum_order_quantity: "100.000",
            order_multiple: "25.000",
            valid_from: null,
            valid_until: null,
        },
    ],
};

const readiness = (isReady = true, blockingReasons = [], warnings = []) => ({
    purchase_requisition_id: 42,
    is_ready: isReady,
    blocking_reasons: blockingReasons,
    warnings,
    item_results: [],
    checked_at: "2026-08-24T12:00:00.000000Z",
});

const mountPage = (status, overrides = {}, extraProps = {}) =>
    shallowMount(PurchaseRequisitionShow, {
        props: {
            purchaseRequisition: { ...requisition(status), ...overrides },
            supplierCandidates: [],
            canSelectSupplier: false,
            canCalculateReplenishment: false,
            executionReadiness: readiness(status === "approved"),
            ...extraProps,
        },
        global: {
            stubs: {
                AdminLayout: LayoutStub,
                Button: ButtonStub,
                Column: true,
                ConfirmDialog: true,
                DataTable: PassthroughStub,
                DatePicker: true,
                Dialog: PassthroughStub,
                Head: true,
                Link: true,
                Tag: true,
            },
        },
    });

describe("Purchase Requisition workflow pending states", () => {
    beforeEach(() => {
        services.toast.add.mockReset();
        services.confirm.require.mockReset();
    });

    it.each(["draft", "requested"])(
        "csak jóváhagyható %s állapotban indít approve kérést",
        async (status) => {
            const wrapper = mountPage(status);

            wrapper.vm.approve();
            const confirmation = services.confirm.require.mock.calls[0][0];
            confirmation.accept();
            confirmation.accept();
            await nextTick();

            expect(inertiaRouter.patch).toHaveBeenCalledOnce();
            expect(inertiaRouter.patch).toHaveBeenCalledWith(
                "/admin/purchase-requisitions/42/approve",
                {},
                expect.any(Object),
            );
            expect(wrapper.vm.approving).toBe(true);
            expect(wrapper.props("purchaseRequisition").status).toBe(status);

            const callbacks = inertiaRouter.patch.mock.calls[0][2];
            callbacks.onFinish();
            await nextTick();

            expect(wrapper.vm.approving).toBe(false);
            expect(services.toast.add).not.toHaveBeenCalled();
        },
    );

    it("approve hiba után feloldja a pending állapotot és biztonságos toastot ad", () => {
        const wrapper = mountPage("requested");
        wrapper.vm.approve();
        services.confirm.require.mock.calls[0][0].accept();

        const callbacks = inertiaRouter.patch.mock.calls[0][2];
        callbacks.onError({
            status: 500,
            message: "SQLSTATE must stay hidden",
        });
        callbacks.onFinish();

        expect(wrapper.vm.approving).toBe(false);
        expect(wrapper.props("purchaseRequisition").status).toBe("requested");
        expect(services.toast.add).toHaveBeenCalledWith({
            severity: "error",
            summary: "notifications.error.server",
            life: 5000,
        });
    });

    it("csak approved állapotban generál PO-t, és blokkolja a dupla kérést", async () => {
        const wrapper = mountPage("approved");
        wrapper.vm.generatePo();
        wrapper.vm.generatePo();
        await nextTick();

        expect(wrapper.vm.form.post).toHaveBeenCalledOnce();
        expect(wrapper.vm.form.post).toHaveBeenCalledWith(
            "/admin/purchase-requisitions/42/generate-purchase-order",
            expect.objectContaining({ preserveScroll: true }),
        );
        expect(wrapper.vm.generating).toBe(true);
        expect(wrapper.props("purchaseRequisition").status).toBe("approved");

        const callbacks = wrapper.vm.form.post.mock.calls[0][1];
        callbacks.onFinish();
        await nextTick();

        expect(wrapper.vm.generating).toBe(false);
        expect(services.toast.add).not.toHaveBeenCalled();
    });

    it("shows READY with warnings and keeps the PO action available", () => {
        const wrapper = mountPage(
            "approved",
            {},
            {
                executionReadiness: readiness(
                    true,
                    [],
                    [
                        {
                            code: "PRICE_MISSING",
                            purchase_requisition_item_id: 1,
                            parameters: { item: "MAT-1" },
                        },
                    ],
                ),
            },
        );

        expect(wrapper.vm.canGeneratePo).toBe(true);
        expect(wrapper.text()).toContain(
            "procurement.execution_readiness.ready",
        );
        expect(wrapper.text()).toContain(
            "procurement.execution_readiness.reasons.price_missing",
        );
    });

    it("shows structured blockers and disables PO generation for NOT READY", () => {
        const blocker = {
            code: "REPLENISHMENT_STALE",
            purchase_requisition_item_id: 1,
            parameters: { item: "MAT-1" },
        };
        const wrapper = mountPage(
            "approved",
            {},
            { executionReadiness: readiness(false, [blocker]) },
        );

        expect(wrapper.vm.canShowGeneratePo).toBe(true);
        expect(wrapper.vm.canGeneratePo).toBe(false);
        expect(wrapper.text()).toContain(
            "procurement.execution_readiness.not_ready",
        );
        expect(wrapper.text()).toContain(
            "procurement.execution_readiness.reasons.replenishment_stale",
        );

        wrapper.vm.generatePo();
        expect(wrapper.vm.form.post).not.toHaveBeenCalled();
    });

    it.each([
        "SUPPLIER_MISSING",
        "ITEM_SUPPLIER_INVALID",
        "REPLENISHMENT_NOT_CALCULATED",
        "QUANTITY_INVARIANT_FAILED",
    ])("renders the %s blocker through i18n", (code) => {
        const wrapper = mountPage(
            "approved",
            {},
            {
                executionReadiness: readiness(false, [
                    {
                        code,
                        purchase_requisition_item_id: 1,
                        parameters: { item: "MAT-1" },
                    },
                ]),
            },
        );

        expect(wrapper.text()).toContain(
            `procurement.execution_readiness.reasons.${code.toLowerCase()}`,
        );
    });

    it("generation hiba után újrapróbálható és nem változtat lokális státuszt", () => {
        const wrapper = mountPage("approved");
        wrapper.vm.generatePo();

        const callbacks = wrapper.vm.form.post.mock.calls[0][1];
        callbacks.onError({ status: 409, message: "Internal conflict" });
        callbacks.onFinish();
        wrapper.vm.generatePo();

        expect(wrapper.vm.form.post).toHaveBeenCalledTimes(2);
        expect(wrapper.props("purchaseRequisition").status).toBe("approved");
        expect(services.toast.add).toHaveBeenCalledWith({
            severity: "error",
            summary: "notifications.error.conflict",
            life: 5000,
        });
    });

    it("renders backend execution-readiness validation and sends no supplier override", async () => {
        const wrapper = mountPage("approved", {
            supplier_id: 7,
            supplier: { id: 7, name: "Supplier" },
        });
        wrapper.vm.form.errors.execution_readiness = [
            "The procurement quantity must be recalculated.",
        ];
        await nextTick();

        expect(wrapper.text()).toContain(
            "The procurement quantity must be recalculated.",
        );
        expect(wrapper.vm.form.supplier_id).toBeUndefined();
    });

    it("egy másik pending művelet alatt nem indít generation kérést", () => {
        const wrapper = mountPage("approved");
        wrapper.vm.approving = true;

        wrapper.vm.generatePo();

        expect(wrapper.vm.form.post).not.toHaveBeenCalled();
    });

    it("shows deterministic supplier candidates only for an authorized supplierless draft", () => {
        const wrapper = mountPage(
            "draft",
            {},
            { supplierCandidates: [candidate], canSelectSupplier: true },
        );

        expect(wrapper.vm.canOpenSupplierSelection).toBe(true);
        expect(wrapper.vm.supplierCandidates).toEqual([candidate]);
        expect(wrapper.vm.supplierForm.patch).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain(
            "procurement.supplier_selection.actions.select",
        );
    });

    it("submits one explicit supplier choice and releases pending state after validation failure", () => {
        const wrapper = mountPage(
            "draft",
            {},
            { supplierCandidates: [candidate], canSelectSupplier: true },
        );

        wrapper.vm.selectSupplier(candidate);
        wrapper.vm.selectSupplier(candidate);

        expect(wrapper.vm.supplierForm.patch).toHaveBeenCalledOnce();
        expect(wrapper.vm.supplierForm.patch).toHaveBeenCalledWith(
            "/admin/purchase-requisitions/42/supplier",
            expect.objectContaining({ preserveScroll: true }),
        );
        expect(wrapper.vm.supplierForm.supplier_id).toBe(7);
        expect(wrapper.vm.selectingSupplier).toBe(true);

        const callbacks = wrapper.vm.supplierForm.patch.mock.calls[0][1];
        callbacks.onError({ supplier_id: "no longer eligible" });
        callbacks.onFinish();

        expect(wrapper.vm.selectingSupplier).toBe(false);
        expect(services.toast.add).toHaveBeenCalled();
    });

    it("shows an explicit no-candidate state without mutating the requisition", () => {
        const wrapper = mountPage(
            "draft",
            {},
            { supplierCandidates: [], canSelectSupplier: true },
        );

        expect(wrapper.text()).toContain(
            "procurement.supplier_selection.no_eligible",
        );
        expect(wrapper.vm.supplierForm.patch).not.toHaveBeenCalled();
        expect(wrapper.props("purchaseRequisition").supplier_id).toBeNull();
    });

    it("shows proposal lineage state and keeps a requisition supplier fixed for PO generation", () => {
        const wrapper = mountPage("approved", {
            supplier_id: 7,
            supplier: { id: 7, name: "Supplier" },
            items: [
                {
                    id: 1,
                    proposal_sources: [
                        {
                            id: 2,
                            supply_proposal_id: 10,
                            quantity: "6.000",
                        },
                    ],
                },
            ],
        });

        expect(wrapper.vm.hasProposalSources).toBe(true);
        expect(wrapper.vm.form.supplier_id).toBeUndefined();
    });

    it("calculates replenishment explicitly only for an authorized supplier-resolved draft", async () => {
        const wrapper = mountPage(
            "draft",
            {
                supplier_id: 7,
                supplier: { id: 7, name: "Supplier" },
                items: [
                    {
                        id: 1,
                        planned_quantity: "10.000",
                        quantity: "15.000",
                        replenishment_excess_quantity: "5.000",
                        replenishment_strategy: "moq_and_order_multiple",
                        replenishment_minimum_order_quantity: "12.000",
                        replenishment_order_multiple: "5.000",
                        proposal_sources: [],
                    },
                ],
            },
            { canCalculateReplenishment: true },
        );

        expect(wrapper.vm.canCalculateReplenishment).toBe(true);
        expect(wrapper.text()).toContain(
            "procurement.replenishment.actions.calculate",
        );

        wrapper.vm.calculateReplenishment();
        wrapper.vm.calculateReplenishment();
        await nextTick();

        expect(inertiaRouter.patch).toHaveBeenCalledOnce();
        expect(inertiaRouter.patch).toHaveBeenCalledWith(
            "/admin/purchase-requisitions/42/replenishment",
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
        expect(wrapper.vm.calculatingReplenishment).toBe(true);

        inertiaRouter.patch.mock.calls[0][2].onFinish();
        await nextTick();
        expect(wrapper.vm.calculatingReplenishment).toBe(false);
    });

    it.each([
        ["draft", null],
        ["requested", 7],
        ["approved", 7],
        ["ordered", 7],
        ["cancelled", 7],
    ])(
        "does not offer replenishment for %s with supplier %s",
        (status, supplierId) => {
            const wrapper = mountPage(
                status,
                { supplier_id: supplierId },
                { canCalculateReplenishment: true },
            );

            expect(wrapper.vm.canCalculateReplenishment).toBe(false);
        },
    );
});
