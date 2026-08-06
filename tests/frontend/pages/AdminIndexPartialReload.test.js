import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CustomerIndex from "@/Pages/Admin/Customers/Index.vue";
import CustomerOrderIndex from "@/Pages/Admin/CustomerOrders/Index.vue";
import FactoryUnitIndex from "@/Pages/Admin/FactoryUnits/Index.vue";
import GoodsReceiptIndex from "@/Pages/Admin/GoodsReceipts/Index.vue";
import MaterialRequirementIndex from "@/Pages/Admin/Inventory/MaterialRequirements/Index.vue";
import StockMovementIndex from "@/Pages/Admin/Inventory/StockMovements/Index.vue";
import StockReservationIndex from "@/Pages/Admin/Inventory/StockReservations/Index.vue";
import StockBalanceIndex from "@/Pages/Admin/Inventory/StockBalances/Index.vue";
import ShortageIndex from "@/Pages/Admin/Inventory/Shortages/Index.vue";
import ItemIndex from "@/Pages/Admin/Items/Index.vue";
import LocationIndex from "@/Pages/Admin/Locations/Index.vue";
import OperationTypeIndex from "@/Pages/Admin/OperationTypes/Index.vue";
import PermissionIndex from "@/Pages/Admin/Permissions/Index.vue";
import ProfessionalRoleIndex from "@/Pages/Admin/ProfessionalRoles/Index.vue";
import PurchaseRequisitionIndex from "@/Pages/Admin/PurchaseRequisitions/Index.vue";
import PurchaseOrderIndex from "@/Pages/Admin/PurchaseOrders/Index.vue";
import RoleIndex from "@/Pages/Admin/Roles/Index.vue";
import SupplierIndex from "@/Pages/Admin/Suppliers/Index.vue";
import UserIndex from "@/Pages/Admin/Users/Index.vue";
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
        name: "Shortages",
        component: ShortageIndex,
        props: { records, filters },
    },
    {
        name: "Material Requirements",
        component: MaterialRequirementIndex,
        props: {
            records,
            filters,
            statusOptions: [],
            itemOptions: [],
            customerOrderOptions: [],
        },
    },
    {
        name: "Stock Reservations",
        component: StockReservationIndex,
        props: {
            records,
            filters,
            statusOptions: [],
        },
    },
    {
        name: "Stock Movements",
        component: StockMovementIndex,
        props: {
            records,
            filters,
            movementTypeOptions: [],
            itemOptions: [],
            locationOptions: [],
        },
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
    {
        name: "Purchase Requisitions",
        component: PurchaseRequisitionIndex,
        props: {
            records,
            filters,
            statusOptions: [],
            itemOptions: [],
        },
    },
    {
        name: "Purchase Orders",
        component: PurchaseOrderIndex,
        props: {
            records,
            filters,
            statusOptions: [],
            supplierOptions: [],
            itemOptions: [],
        },
    },
    {
        name: "Goods Receipts",
        component: GoodsReceiptIndex,
        props: {
            records,
            filters,
            statusOptions: [],
            purchaseOrderOptions: [],
            itemOptions: [],
            locationOptions: [],
        },
    },
    {
        name: "Factory Units",
        component: FactoryUnitIndex,
        props: { records, filters },
    },
    {
        name: "Locations",
        component: LocationIndex,
        props: {
            records,
            filters,
            options: { factoryUnits: [], locationTypes: [] },
        },
    },
    {
        name: "Professional Roles",
        component: ProfessionalRoleIndex,
        props: { records, filters },
    },
    {
        name: "Operation Types",
        component: OperationTypeIndex,
        props: { records, filters, operationTypeCodes: [] },
    },
    {
        name: "Users",
        component: UserIndex,
        props: { records, filters, options: { roles: [] } },
    },
    {
        name: "Roles",
        component: RoleIndex,
        props: { records, filters, options: { permissions: [] } },
    },
    {
        name: "Permissions",
        component: PermissionIndex,
        props: { records, filters },
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
        expect(services.toast.add).not.toHaveBeenCalled();
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

        wrapper.vm.refreshRecords();
        expect(inertiaRouter.reload).toHaveBeenCalledTimes(2);
    });
});
