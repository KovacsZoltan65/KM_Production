import { defineComponent } from "vue";
import { describe, expect, it } from "vitest";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { NAVIGATION_PERMISSIONS } from "@/Utils/navigation";
import { mountWithApp } from "../helpers/mount.js";
import { inertiaPage, inertiaRouter } from "../mocks/inertia.js";
import { makeAuthPageProps } from "../fixtures/domain.js";

const LinkStub = defineComponent({
    name: "Link",
    props: ["href"],
    template: "<a :href='href'><slot /></a>",
});
const MenuStub = defineComponent({
    name: "Menu",
    props: ["model"],
    expose: ["toggle"],
    methods: { toggle() {} },
    template: "<div data-test='user-menu' />",
});
const TopbarLocaleSwitchStub = defineComponent({
    name: "TopbarLocaleSwitch",
    template: "<div data-test='locale-switch' />",
});
const ButtonStub = defineComponent({
    name: "Button",
    template: "<button><slot /></button>",
});

const mountLayout = ({ permissions = [], roles = [], url = "/" } = {}) => {
    inertiaPage.props = makeAuthPageProps({
        auth: {
            permissions,
            roles,
            user: { id: 1, name: "Minta Anna", email: "anna@example.test" },
        },
    });
    inertiaPage.url = url;

    return mountWithApp(AdminLayout, {
        slots: { default: "<div data-test='page-content'>Tartalom</div>" },
        global: {
            stubs: {
                Link: LinkStub,
                Menu: MenuStub,
                Button: ButtonStub,
                Avatar: true,
                TopbarLocaleSwitch: TopbarLocaleSwitchStub,
            },
        },
    });
};

describe("AdminLayout", () => {
    it("teljes jogosultságkészlettel megjeleníti a fő modulokat", () => {
        const wrapper = mountLayout({
            permissions: [...new Set(Object.values(NAVIGATION_PERMISSIONS))],
        });

        expect(wrapper.text()).toContain("navigation.inventory");
        expect(wrapper.text()).toContain("navigation.procurement_dashboard");
        expect(wrapper.text()).toContain("navigation.document_library");
        expect(wrapper.text()).toContain("navigation.intelligence");
        expect(wrapper.text()).toContain("navigation.quality_report");
    });

    it("üres permission listánál nem mutat védett menüpontot", () => {
        const wrapper = mountLayout();

        expect(wrapper.find("nav").text()).toBe("");
        expect(wrapper.find("[data-test='page-content']").exists()).toBe(true);
    });

    it("egyetlen inventory permission csak a készletmodult és csoportját mutatja", () => {
        const wrapper = mountLayout({ permissions: ["inventory.view"] });

        expect(wrapper.find("nav").text()).toContain("navigation.production");
        expect(wrapper.find("nav").text()).toContain("navigation.inventory");
        expect(wrapper.find("nav").text()).toContain("navigation.reservations");
        expect(wrapper.find("nav").text()).not.toContain(
            "navigation.procurement_dashboard",
        );
    });

    it("super-admin szerepkör üres permission listával is teljes menüt kap", () => {
        const wrapper = mountLayout({ roles: ["super-admin"] });

        expect(wrapper.find("nav").text()).toContain("navigation.users");
        expect(wrapper.find("nav").text()).toContain(
            "navigation.document_library",
        );
    });

    it("a legpontosabb inventory aloldalt jelöli aktívnak query mellett", () => {
        const wrapper = mountLayout({
            permissions: ["inventory.view"],
            url: "/admin/inventory/stock-reservations?status=active",
        });
        const active = wrapper.find("nav [aria-current='page']");

        expect(active.attributes("href")).toBe(
            "/admin/inventory/stock-reservations",
        );
        expect(active.text()).toContain("navigation.reservations");
    });

    it("megjeleníti a felhasználót és a locale switchert", () => {
        const wrapper = mountLayout({ permissions: ["dashboard.view"] });

        expect(wrapper.text()).toContain("Minta Anna");
        expect(wrapper.text()).toContain("anna@example.test");
        expect(wrapper.find("[data-test='locale-switch']").exists()).toBe(true);
    });

    it("hiányzó user adatnál biztonságos avatar-alapértéket használ", () => {
        inertiaPage.props = makeAuthPageProps({
            auth: { user: null, permissions: [], roles: [] },
        });
        const wrapper = mountWithApp(AdminLayout, {
            global: {
                stubs: {
                    Link: LinkStub,
                    Menu: MenuStub,
                    Button: ButtonStub,
                    Avatar: true,
                    TopbarLocaleSwitch: TopbarLocaleSwitchStub,
                },
            },
        });

        expect(wrapper.find("avatar-stub").attributes("label")).toBe("U");
    });

    it("a profil és logout menü a megfelelő Inertia műveletet indítja", () => {
        const wrapper = mountLayout();
        const menu = wrapper.findComponent(MenuStub).props("model");

        menu[0].command();
        menu[1].command();

        expect(inertiaRouter.visit).toHaveBeenCalledWith("/profile");
        expect(inertiaRouter.post).toHaveBeenCalledWith("/logout");
    });

    it("a sidebar és a fő tartalom külön görgetési konténer", () => {
        const wrapper = mountLayout({ permissions: ["dashboard.view"] });

        expect(wrapper.find("nav").classes()).toContain("md:overflow-y-auto");
        expect(wrapper.find("main").classes()).toContain("overflow-y-auto");
        expect(wrapper.find("aside").classes()).toContain("overflow-hidden");
    });
});
