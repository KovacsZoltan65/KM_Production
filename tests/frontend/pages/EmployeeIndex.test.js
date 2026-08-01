import { defineComponent, nextTick } from "vue";
import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import EmployeeIndex from "@/Pages/Admin/Employees/Index.vue";
import { inertiaRouter } from "../mocks/inertia.js";

const services = {
    toast: { add: vi.fn() },
};

vi.mock("primevue/usetoast", () => ({
    useToast: () => services.toast,
}));

const AdminCrudPageStub = defineComponent({
    name: "AdminCrudPage",
    template: "<section><slot name='header-actions' /></section>",
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
    data: [{ id: 1, employee_number: "EMP-001", name: "Meglévő dolgozó" }],
    current_page: 3,
    per_page: 25,
    total: 80,
    last_page: 4,
};

const mountPage = () =>
    shallowMount(EmployeeIndex, {
        props: {
            records,
            filters: {
                search: "hegesztő",
                per_page: 25,
                sort: "name",
                direction: "desc",
            },
            options: { professionalRoles: [], users: [] },
        },
        global: {
            stubs: {
                AdminCrudPage: AdminCrudPageStub,
                Button: ButtonStub,
            },
        },
    });

describe("Employees Index refresh", () => {
    beforeEach(() => {
        services.toast.add.mockReset();
    });

    it("lokalizált, elérhető frissítés gombot jelenít meg", () => {
        const button = mountPage().findComponent(ButtonStub);

        expect(button.props()).toMatchObject({
            label: "actions.refresh",
            icon: "pi pi-refresh",
            loading: false,
            disabled: false,
        });
    });

    it("csak a records propot tölti újra az aktuális URL és állapot megtartásával", async () => {
        const wrapper = mountPage();

        await wrapper.findComponent(ButtonStub).trigger("click");

        expect(inertiaRouter.reload).toHaveBeenCalledWith(
            expect.objectContaining({
                only: ["records"],
                preserveScroll: true,
                preserveState: true,
            }),
        );
        expect(inertiaRouter.get).not.toHaveBeenCalled();
        expect(inertiaRouter.visit).not.toHaveBeenCalled();
    });

    it("frissítés közben loading állapotot jelez és letiltja az újabb kérést", async () => {
        const wrapper = mountPage();
        await wrapper.findComponent(ButtonStub).trigger("click");
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

    it("hiba esetén biztonságos üzenetet ad és megtartja a jelenlegi rekordokat", async () => {
        const wrapper = mountPage();
        wrapper.vm.refreshRecords();
        const callbacks = inertiaRouter.reload.mock.calls[0][0];

        callbacks.onStart();
        callbacks.onError({ message: "SQLSTATE should stay hidden" });
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
