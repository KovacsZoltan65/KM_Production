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
    items: [],
});

const mountPage = (status) =>
    shallowMount(PurchaseRequisitionShow, {
        props: {
            purchaseRequisition: requisition(status),
            supplierOptions: [{ id: 7, label: "SUP-7 - Supplier" }],
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
                Select: true,
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
        wrapper.vm.form.supplier_id = 7;

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

    it("egy másik pending művelet alatt nem indít generation kérést", () => {
        const wrapper = mountPage("approved");
        wrapper.vm.approving = true;

        wrapper.vm.generatePo();

        expect(wrapper.vm.form.post).not.toHaveBeenCalled();
    });
});
