import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import GoodsReceiptShow from "@/Pages/Admin/GoodsReceipts/Show.vue";
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

const receipt = (status) => ({
    id: 42,
    receipt_number: "GR-TEST-0042",
    status,
    purchase_order: { order_number: "PO-42", supplier: { name: "Supplier" } },
    items: [{ id: 1, quantity: "4.000", item: null, location: null }],
});

const mountPage = (status) =>
    shallowMount(GoodsReceiptShow, {
        props: { goodsReceipt: receipt(status) },
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

describe("Goods Receipt posting pending state", () => {
    beforeEach(() => {
        services.toast.add.mockReset();
        services.confirm.require.mockReset();
    });

    it.each([
        ["draft", 1],
        ["posted", 0],
    ])("%s állapotban a posting action darabszáma %i", (status, count) => {
        expect(mountPage(status).findAllComponents(ButtonStub)).toHaveLength(
            count,
        );
    });

    it("confirm után pontosan egy POST-ot indít és pending alatt tilt", async () => {
        const wrapper = mountPage("draft");
        wrapper.vm.postReceipt();
        const confirmation = services.confirm.require.mock.calls[0][0];
        confirmation.accept();
        confirmation.accept();
        await nextTick();

        expect(inertiaRouter.post).toHaveBeenCalledOnce();
        expect(inertiaRouter.post).toHaveBeenCalledWith(
            "/admin/goods-receipts/42/post",
            {},
            expect.any(Object),
        );
        expect(wrapper.vm.posting).toBe(true);
        expect(wrapper.findComponent(ButtonStub).props()).toMatchObject({
            loading: true,
            disabled: true,
        });
        expect(wrapper.props("goodsReceipt").status).toBe("draft");

        inertiaRouter.post.mock.calls[0][2].onFinish();
        await nextTick();
        expect(wrapper.vm.posting).toBe(false);
        expect(services.toast.add).not.toHaveBeenCalled();
    });

    it("hiba után biztonságos toastot ad, felold és újrapróbálható", () => {
        const wrapper = mountPage("draft");
        wrapper.vm.postReceipt();
        services.confirm.require.mock.calls[0][0].accept();

        const callbacks = inertiaRouter.post.mock.calls[0][2];
        callbacks.onError({
            status: 500,
            message: "SQLSTATE must stay hidden",
        });
        callbacks.onFinish();
        wrapper.vm.postReceipt();
        services.confirm.require.mock.calls[1][0].accept();

        expect(inertiaRouter.post).toHaveBeenCalledTimes(2);
        expect(wrapper.props("goodsReceipt").status).toBe("draft");
        expect(services.toast.add).toHaveBeenCalledWith({
            severity: "error",
            summary: "notifications.error.server",
            life: 5000,
        });
    });
});
