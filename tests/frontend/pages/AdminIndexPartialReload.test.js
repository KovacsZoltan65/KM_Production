import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CustomerIndex from "@/Pages/Admin/Customers/Index.vue";
import CustomerOrderIndex from "@/Pages/Admin/CustomerOrders/Index.vue";
import StockBalanceIndex from "@/Pages/Admin/Inventory/StockBalances/Index.vue";
import ItemIndex from "@/Pages/Admin/Items/Index.vue";
import SupplierIndex from "@/Pages/Admin/Suppliers/Index.vue";
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

const HeaderContainerStub = defineComponent({
    name: "HeaderContainer",
    template: `
        <section>
            <slot name="header-actions" />
            <slot name="actions" />
        </section>
    `,
});

const LayoutStub = defineComponent({
    name: "AdminLayout",
    template: "<main><slot /></main>",
});

const ButtonStub = defineComponent({
    name: "Button",
    inheritAttrs: false,
    props: ["label", "icon", "loading", "disabled"],
    emits: ["click"],
    template: `
        <button
            :disabled="disabled"
            :data-icon="icon"
            :data-loading="String(Boolean(loading))"
            @click="$emit('click')"
        >{{ label }}</button>
    `,
});

const records = {
    data: [{ id: 1, name: "Existing record" }],
    current_page: 3,
    per_page: 25,
    total: 80,
    last_page: 4,
};

const filters = {
    search: "needle",
    status: "active",
    per_page: 25,
    sort: "name",
    direction: "desc",
};

const pages = [
    {
        name: "Items",
        component: ItemIndex,
        props: { records, filters, itemTypes: [] },
    },
    {
        name: "Customers",
        component: CustomerIndex,
        props: { records, filters },
    },
    {
        name: "Suppliers",
        component: SupplierIndex,
        props: { records, filters },
    },
    {
        name: "Stock Balances",
        component: StockBalanceIndex,
        props: { records, filters },
    },
    {
        name: "Customer Orders",
        component: CustomerOrderIndex,
        props: {
            records,
            filters,
            customerOptions: [],
            itemOptions: [],
            statusOptions: [],
        },
    },
];

const mountPage = ({ component, props }) =>
    shallowMount(component, {
        props,
        global: {
            stubs: {
                AdminCrudPage: HeaderContainerStub,
                AdminPageHeader: HeaderContainerStub,
                AdminLayout: LayoutStub,
                Button: ButtonStub,
                Head: true,
            },
        },
    });

describe.each(pages)("$name Index refresh", (pageDefinition) => {
    beforeEach(() => {
        services.toast.add.mockReset();
        services.confirm.require.mockReset();
    });

    it("lokalizált frissítés gombot jelenít meg", () => {
        const button = mountPage(pageDefinition).findComponent(ButtonStub);

        expect(button.props()).toMatchObject({
            label: "actions.refresh",
            icon: "pi pi-refresh",
            loading: false,
            disabled: false,
        });
    });

    it("csak a records propot tölti újra navigáció nélkül", async () => {
        const wrapper = mountPage(pageDefinition);

        await wrapper.findComponent(ButtonStub).trigger("click");

        expect(inertiaRouter.reload).toHaveBeenCalledWith(
            expect.objectContaining({
                only: ["records"],
                preserveState: true,
                preserveScroll: true,
            }),
        );
        expect(inertiaRouter.get).not.toHaveBeenCalled();
        expect(inertiaRouter.visit).not.toHaveBeenCalled();
    });

    it("loading közben letiltja a gombot és a dupla kérést", async () => {
        const wrapper = mountPage(pageDefinition);
        wrapper.vm.refreshRecords();
        const callbacks = inertiaRouter.reload.mock.calls[0][0];

        callbacks.onStart();
        await nextTick();

        expect(wrapper.findComponent(ButtonStub).props()).toMatchObject({
            loading: true,
            disabled: true,
        });

        wrapper.vm.refreshRecords();
        expect(inertiaRouter.reload).toHaveBeenCalledOnce();

        callbacks.onFinish();
        await nextTick();

        expect(wrapper.findComponent(ButtonStub).props()).toMatchObject({
            loading: false,
            disabled: false,
        });
    });

    it("hibánál lokalizált toastot ad és megtartja a rekordokat", async () => {
        const wrapper = mountPage(pageDefinition);
        wrapper.vm.refreshRecords();
        const callbacks = inertiaRouter.reload.mock.calls[0][0];

        callbacks.onStart();
        callbacks.onError({ message: "SQLSTATE must remain hidden" });
        callbacks.onFinish();
        await nextTick();

        expect(services.toast.add).toHaveBeenCalledWith({
            severity: "error",
            summary: "notifications.error.refresh_failed",
            life: 5000,
        });
        expect(wrapper.props("records").data).toEqual(records.data);
        expect(wrapper.vm.refreshing).toBe(false);
    });
});
