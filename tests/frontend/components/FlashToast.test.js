import { defineComponent, h, nextTick, onMounted } from "vue";
import { shallowMount } from "@vue/test-utils";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import FlashToast from "@/Components/Common/FlashToast.vue";
import { inertiaPage, inertiaRouter } from "../mocks/inertia.js";

const services = vi.hoisted(() => ({
    toast: { add: vi.fn() },
    toastMounted: false,
}));

vi.mock("primevue/usetoast", () => ({
    useToast: () => services.toast,
}));

let wrapper;
const ToastStub = defineComponent({
    name: "Toast",
    setup() {
        onMounted(() => {
            services.toastMounted = true;
        });

        return () => h("div");
    },
});
const mountHandler = () => {
    wrapper = shallowMount(FlashToast, {
        global: { stubs: { Toast: ToastStub } },
    });

    return wrapper;
};

describe("FlashToast", () => {
    beforeEach(() => {
        services.toast.add.mockReset();
        services.toastMounted = false;
        inertiaRouter.on.mockImplementation(() => vi.fn());
    });

    afterEach(() => wrapper?.unmount());

    it.each([
        ["success", "success", 2500],
        ["error", "error", 5000],
        ["warning", "warn", 4000],
        ["info", "info", 3500],
    ])(
        "a(z) %s flash üzenetet megfelelő toastként jeleníti meg",
        (key, severity, life) => {
            inertiaPage.props.flash = { [key]: "Visszajelzés" };

            mountHandler();

            expect(services.toast.add).toHaveBeenCalledWith({
                severity,
                summary: "Visszajelzés",
                life,
            });
        },
    );

    it("az első flasht csak a Toast komponens mountja után küldi ki", () => {
        inertiaPage.props.flash = { success: "Átirányítás után" };
        services.toast.add.mockImplementationOnce(() => {
            expect(services.toastMounted).toBe(true);
        });

        mountHandler();

        expect(services.toast.add).toHaveBeenCalledOnce();
    });

    it("a későbbi Inertia prop frissítést megjeleníti", async () => {
        inertiaPage.props.flash = {};
        mountHandler();

        inertiaPage.props.flash = { success: "Elmentve" };
        await nextTick();

        expect(services.toast.add).toHaveBeenCalledOnce();
    });

    it("azonos prop-frissítést nem jelenít meg kétszer", async () => {
        inertiaPage.props.flash = { success: "Elmentve" };
        mountHandler();

        inertiaPage.props.flash = { success: "Elmentve" };
        await nextTick();

        expect(services.toast.add).toHaveBeenCalledOnce();
    });

    it("ürítés után egy új azonos flash ismét megjelenhet", async () => {
        inertiaPage.props.flash = { success: "Elmentve" };
        mountHandler();

        inertiaPage.props.flash = {};
        await nextTick();
        inertiaPage.props.flash = { success: "Elmentve" };
        await nextTick();

        expect(services.toast.add).toHaveBeenCalledTimes(2);
    });

    it("a validációs és hálózati Inertia hibákat biztonságos toasttal jelzi", () => {
        const listeners = {};
        inertiaRouter.on.mockImplementation((event, callback) => {
            listeners[event] = callback;
            return vi.fn();
        });
        mountHandler();

        listeners.error();
        listeners.networkError({ detail: { error: new Error("offline") } });

        expect(services.toast.add).toHaveBeenNthCalledWith(
            1,
            expect.objectContaining({
                summary: "notifications.error.validation",
            }),
        );
        expect(services.toast.add).toHaveBeenNthCalledWith(
            2,
            expect.objectContaining({
                summary: "notifications.error.network",
            }),
        );
    });
});
