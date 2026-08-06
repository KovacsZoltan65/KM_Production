import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import PurchaseOrderShow from "@/Pages/Admin/PurchaseOrders/Show.vue";
import { inertiaRouter } from "../mocks/inertia.js";

const services = {
    toast: { add: vi.fn() },
    confirm: { require: vi.fn() },
};

vi.mock("primevue/usetoast", () => ({ useToast: () => services.toast }));
vi.mock("primevue/useconfirm", () => ({ useConfirm: () => services.confirm }));

const LayoutStub = defineComponent({
    name: "AdminLayout",
    template: "<main><slot /></main>",
});

const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "loading", "disabled"],
    emits: ["click"],
    template: `<button :disabled="disabled" :data-loading="String(Boolean(loading))" @click="$emit('click')">{{ label }}</button>`,
});

const order = (status) => ({
    id: 42,
    order_number: "PO-TEST-0042",
    status,
    supplier: { name: "Supplier" },
    items: [],
});

const mountPage = (status) =>
    shallowMount(PurchaseOrderShow, {
        props: { purchaseOrder: order(status) },
        global: {
            stubs: {
                AdminLayout: LayoutStub,
                Button: ButtonStub,
                Column: true,
                ConfirmDialog: true,
                DataTable: true,
                Head: true,
                Link: true,
                Tag: true,
            },
        },
    });

describe("Purchase Order workflow pending states", () => {
    beforeEach(() => {
        services.toast.add.mockReset();
        services.confirm.require.mockReset();
    });

    it.each([
        ["draft", ["actions.approve"]],
        ["ordered", ["actions.close"]],
        ["partially_received", ["actions.close"]],
        ["received", []],
        ["cancelled", []],
    ])(
        "%s állapotban csak az engedélyezett action látható",
        (status, labels) => {
            const renderedLabels = mountPage(status)
                .findAllComponents(ButtonStub)
                .map((button) => button.props("label"));

            expect(renderedLabels).toEqual(labels);
        },
    );

    it("approve alatt blokkolja a dupla és az eltérő műveletet", async () => {
        const wrapper = mountPage("draft");
        wrapper.vm.approve();
        const confirmation = services.confirm.require.mock.calls[0][0];
        confirmation.accept();
        confirmation.accept();
        wrapper.vm.close();
        services.confirm.require.mock.calls[1][0].accept();
        await nextTick();

        expect(inertiaRouter.patch).toHaveBeenCalledOnce();
        expect(inertiaRouter.patch).toHaveBeenCalledWith(
            "/admin/purchase-orders/42/approve",
            {},
            expect.any(Object),
        );
        expect(wrapper.vm.pendingAction).toBe("approve");
        expect(wrapper.props("purchaseOrder").status).toBe("draft");
        expect(wrapper.findComponent(ButtonStub).props()).toMatchObject({
            loading: true,
            disabled: true,
        });

        inertiaRouter.patch.mock.calls[0][2].onFinish();
        await nextTick();
        expect(wrapper.vm.pendingAction).toBeNull();
        expect(services.toast.add).not.toHaveBeenCalled();
    });

    it.each(["ordered", "partially_received"])(
        "%s állapotban close kérést indít és nem módosít optimistán",
        async (status) => {
            const wrapper = mountPage(status);
            wrapper.vm.close();
            services.confirm.require.mock.calls[0][0].accept();
            await nextTick();

            expect(inertiaRouter.patch).toHaveBeenCalledOnce();
            expect(inertiaRouter.patch).toHaveBeenCalledWith(
                "/admin/purchase-orders/42/close",
                {},
                expect.any(Object),
            );
            expect(wrapper.vm.pendingAction).toBe("close");
            expect(wrapper.props("purchaseOrder").status).toBe(status);
        },
    );

    it("hiba után biztonságos toastot ad, felold és újrapróbálható", () => {
        const wrapper = mountPage("ordered");
        wrapper.vm.close();
        services.confirm.require.mock.calls[0][0].accept();

        const callbacks = inertiaRouter.patch.mock.calls[0][2];
        callbacks.onError({
            status: 500,
            message: "SQLSTATE must stay hidden",
        });
        callbacks.onFinish();
        wrapper.vm.close();
        services.confirm.require.mock.calls[1][0].accept();

        expect(inertiaRouter.patch).toHaveBeenCalledTimes(2);
        expect(wrapper.props("purchaseOrder").status).toBe("ordered");
        expect(services.toast.add).toHaveBeenCalledWith({
            severity: "error",
            summary: "notifications.error.server",
            life: 5000,
        });
    });
});
