import { defineComponent, nextTick } from "vue";
import { beforeEach, describe, expect, it, vi } from "vitest";
import StockReservations from "@/Pages/Admin/Inventory/StockReservations/Index.vue";
import { mountWithApp } from "../helpers/mount.js";
import {
    makeAuthPageProps,
    makePagination,
    makeStockReservation,
} from "../fixtures/domain.js";
import { inertiaPage, inertiaRouter } from "../mocks/inertia.js";

const services = vi.hoisted(() => ({
    confirm: { require: vi.fn() },
    toast: { add: vi.fn() },
}));

vi.mock("primevue/useconfirm", () => ({ useConfirm: () => services.confirm }));
vi.mock("primevue/usetoast", () => ({ useToast: () => services.toast }));

const PassthroughStub = defineComponent({ template: "<div><slot /></div>" });
const ButtonStub = defineComponent({
    name: "Button",
    props: ["label", "loading", "disabled"],
    emits: ["click"],
    template:
        "<button :disabled='disabled' @click='$emit(\"click\")'>{{ label }}</button>",
});

const mountPage = ({
    permissions = ["inventory.release"],
    roles = [],
    records = [makeStockReservation()],
} = {}) => {
    inertiaPage.props = makeAuthPageProps({
        auth: { user: null, permissions, roles },
    });

    return mountWithApp(StockReservations, {
        props: {
            records: makePagination(records),
            filters: {},
            statusOptions: [
                { label: "Aktív", value: "active" },
                { label: "Feloldott", value: "released" },
            ],
        },
        global: {
            stubs: {
                AdminLayout: PassthroughStub,
                AdminPageHeader: true,
                AdminSearchBar: true,
                Head: true,
                Toast: true,
                ConfirmDialog: true,
                Select: true,
                Button: ButtonStub,
            },
        },
    });
};

describe("StockReservations", () => {
    beforeEach(() => {
        services.confirm.require.mockReset();
        services.toast.add.mockReset();
    });

    it("aktív foglalásnál permission birtokában elérhető a feloldás", () => {
        const wrapper = mountPage();
        expect(wrapper.findComponent(ButtonStub).text()).toBe("actions.release");
    });

    it.each(["released", "cancelled", null])(
        "%s státusznál nem jelenít meg feloldási műveletet",
        (status) => {
            const wrapper = mountPage({
                records: [makeStockReservation({ status })],
            });
            expect(wrapper.findComponent(ButtonStub).exists()).toBe(false);
        },
    );

    it("inventory.release nélkül nem mutat feloldási műveletet", () => {
        const wrapper = mountPage({ permissions: ["inventory.view"] });
        expect(wrapper.findComponent(ButtonStub).exists()).toBe(false);
    });

    it("super-admin üres permission listával is feloldhat", () => {
        const wrapper = mountPage({ permissions: [], roles: ["super-admin"] });
        expect(wrapper.findComponent(ButtonStub).exists()).toBe(true);
    });

    it("feloldás előtt megerősítést kér és kérés még nem indul", async () => {
        const wrapper = mountPage();
        await wrapper.findComponent(ButtonStub).trigger("click");

        expect(services.confirm.require).toHaveBeenCalledOnce();
        expect(inertiaRouter.patch).not.toHaveBeenCalled();
    });

    it("megerősítés után a megfelelő ID-val PATCH kérést indít", async () => {
        const wrapper = mountPage({
            records: [makeStockReservation({ id: 44 })],
        });
        await wrapper.findComponent(ButtonStub).trigger("click");

        services.confirm.require.mock.calls[0][0].accept();

        expect(inertiaRouter.patch).toHaveBeenCalledWith(
            "/admin/inventory/stock-reservations/44/release",
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it("feldolgozás közben letiltja a műveletet, finish után újra engedi", async () => {
        const wrapper = mountPage();
        await wrapper.findComponent(ButtonStub).trigger("click");
        services.confirm.require.mock.calls[0][0].accept();
        await nextTick();

        expect(wrapper.findComponent(ButtonStub).props()).toMatchObject({
            loading: true,
            disabled: true,
        });
        inertiaRouter.patch.mock.calls[0][2].onFinish();
        await nextTick();
        expect(wrapper.findComponent(ButtonStub).props("disabled")).toBe(false);
    });

    it("feldolgozás közben nem enged második megerősítést", async () => {
        const wrapper = mountPage();
        await wrapper.findComponent(ButtonStub).trigger("click");
        services.confirm.require.mock.calls[0][0].accept();

        wrapper.vm.release(makeStockReservation({ id: 2 }));

        expect(services.confirm.require).toHaveBeenCalledOnce();
        expect(inertiaRouter.patch).toHaveBeenCalledOnce();
    });

    it("üres listánál explicit üres állapotot jelenít meg", () => {
        const wrapper = mountPage({ records: [] });
        expect(wrapper.text()).toContain("common.no_data");
    });

    it("hiányzó nested relationökkel is renderelhető marad", () => {
        const wrapper = mountPage({
            records: [
                makeStockReservation({
                    item: null,
                    location: null,
                    reserver: null,
                    reserved_at: null,
                }),
            ],
        });
        expect(wrapper.exists()).toBe(true);
        expect(wrapper.text()).toContain("-");
    });
});
