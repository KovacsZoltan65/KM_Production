<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { route } from "@/Utils/routes";
import Avatar from "primevue/avatar";
import Button from "primevue/button";
import Menu from "primevue/menu";
import { computed, ref } from "vue";
import { trans } from "laravel-vue-i18n";
import TopbarLocaleSwitch from "@/Components/TopbarLocaleSwitch.vue";

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userMenu = ref();

const menuItems = computed(() => [
    {
        label: trans("navigation.profile"),
        icon: "pi pi-user",
        command: () => router.visit(route("profile.edit")),
    },
    {
        label: trans("navigation.logout"),
        icon: "pi pi-sign-out",
        command: () => router.post(route("logout")),
    },
]);

const sidebarItems = computed(() => [
    {
        labelKey: "admin.dashboard.title",
        icon: "pi pi-home",
        href: route("admin.dashboard"),
    },
    {
        labelKey: "navigation.users",
        icon: "pi pi-users",
        href: route("admin.users.index"),
    },
    {
        labelKey: "navigation.roles",
        icon: "pi pi-shield",
        href: route("admin.roles.index"),
    },
    {
        labelKey: "navigation.permissions",
        icon: "pi pi-key",
        href: route("admin.permissions.index"),
    },
    {
        labelKey: "navigation.employees",
        icon: "pi pi-id-card",
        href: route("admin.employees.index"),
    },
    {
        labelKey: "navigation.factory_units",
        icon: "pi pi-building",
        href: route("admin.factory-units.index"),
    },
    {
        labelKey: "navigation.locations",
        icon: "pi pi-map-marker",
        href: route("admin.locations.index"),
    },
    {
        labelKey: "navigation.professional_roles",
        icon: "pi pi-briefcase",
        href: route("admin.professional-roles.index"),
    },
    {
        labelKey: "navigation.items",
        icon: "pi pi-box",
        href: route("admin.items.index"),
    },
    {
        labelKey: "navigation.boms",
        icon: "pi pi-list-check",
        href: route("admin.boms.index"),
    },
    {
        labelKey: "navigation.operation_types",
        icon: "pi pi-cog",
        href: route("admin.operation-types.index"),
    },
    {
        labelKey: "navigation.operation_sequences",
        icon: "pi pi-sitemap",
        href: route("admin.operation-sequences.index"),
    },
    {
        labelKey: "navigation.business_partners",
        icon: "pi pi-address-book",
        disabled: true,
    },
    {
        labelKey: "navigation.customers",
        icon: "pi pi-user-plus",
        href: route("admin.customers.index"),
    },
    {
        labelKey: "navigation.suppliers",
        icon: "pi pi-truck",
        href: route("admin.suppliers.index"),
    },
    {
        labelKey: "navigation.sales",
        icon: "pi pi-shopping-cart",
        disabled: true,
    },
    {
        labelKey: "navigation.customer_orders",
        icon: "pi pi-file-edit",
        href: route("admin.customer-orders.index"),
    },
    {
        labelKey: "navigation.planning",
        icon: "pi pi-calendar-clock",
        disabled: true,
    },
    {
        labelKey: "navigation.production_plans",
        icon: "pi pi-calendar-plus",
        href: route("admin.production-plans.index"),
    },
    {
        labelKey: "navigation.capacity_dashboard",
        icon: "pi pi-chart-pie",
        href: route("admin.capacity.dashboard"),
    },
    {
        labelKey: "navigation.factory_capacity",
        icon: "pi pi-building-columns",
        href: route("admin.capacity.factory-units"),
    },
    {
        labelKey: "navigation.employee_capacity",
        icon: "pi pi-users",
        href: route("admin.capacity.employees"),
    },
    {
        labelKey: "navigation.capacity_schedule",
        icon: "pi pi-calendar-clock",
        href: route("admin.capacity.schedule"),
    },
    {
        labelKey: "navigation.capacity_simulation",
        icon: "pi pi-sliders-h",
        href: route("admin.capacity.simulate"),
    },
    { labelKey: "navigation.production", icon: "pi pi-cog", disabled: true },
    {
        labelKey: "navigation.shop_floor",
        icon: "pi pi-th-large",
        href: route("admin.shop-floor.index"),
    },
    {
        labelKey: "navigation.my_tasks",
        icon: "pi pi-list-check",
        href: route("admin.shop-floor.my-tasks"),
    },
    {
        labelKey: "navigation.production_tasks",
        icon: "pi pi-play-circle",
        href: route("admin.production-tasks.index"),
    },
    {
        labelKey: "navigation.inventory",
        icon: "pi pi-warehouse",
        disabled: false,
    },
    {
        labelKey: "navigation.stock_balances",
        icon: "pi pi-box",
        href: route("admin.inventory.stock-balances.index"),
    },
    {
        labelKey: "navigation.stock_movements",
        icon: "pi pi-arrow-right-arrow-left",
        href: route("admin.inventory.stock-movements.index"),
    },
    {
        labelKey: "navigation.reservations",
        icon: "pi pi-lock",
        href: route("admin.inventory.stock-reservations.index"),
    },
    {
        labelKey: "navigation.material_requirements",
        icon: "pi pi-list",
        href: route("admin.inventory.material-requirements.index"),
    },
    {
        labelKey: "navigation.shortages",
        icon: "pi pi-exclamation-triangle",
        href: route("admin.inventory.shortages.index"),
    },
    {
        labelKey: "navigation.procurement",
        icon: "pi pi-shopping-bag",
        disabled: true,
    },
    {
        labelKey: "navigation.procurement_dashboard",
        icon: "pi pi-chart-bar",
        href: route("admin.procurement.dashboard"),
    },
    {
        labelKey: "navigation.purchase_requisitions",
        icon: "pi pi-list-check",
        href: route("admin.purchase-requisitions.index"),
    },
    {
        labelKey: "navigation.purchase_orders",
        icon: "pi pi-shopping-cart",
        href: route("admin.purchase-orders.index"),
    },
    {
        labelKey: "navigation.goods_receipts",
        icon: "pi pi-inbox",
        href: route("admin.goods-receipts.index"),
    },
    { labelKey: "navigation.documents", icon: "pi pi-folder", disabled: true },
    {
        labelKey: "navigation.document_library",
        icon: "pi pi-file",
        href: route("admin.documents.index"),
    },
    {
        labelKey: "navigation.reports",
        icon: "pi pi-chart-line",
        disabled: true,
    },
    {
        labelKey: "navigation.customer_orders_report",
        icon: "pi pi-list",
        href: route("admin.reports.customer-orders"),
    },
    {
        labelKey: "navigation.production_report",
        icon: "pi pi-cog",
        href: route("admin.reports.production"),
    },
    {
        labelKey: "navigation.inventory_report",
        icon: "pi pi-warehouse",
        href: route("admin.reports.inventory"),
    },
    {
        labelKey: "navigation.procurement_report",
        icon: "pi pi-shopping-bag",
        href: route("admin.reports.procurement"),
    },
    {
        labelKey: "navigation.quality_report",
        icon: "pi pi-check-circle",
        href: route("admin.reports.quality"),
    },
    {
        labelKey: "navigation.shop_floor_report",
        icon: "pi pi-th-large",
        href: route("admin.reports.shop-floor"),
    },
    {
        labelKey: "navigation.intelligence",
        icon: "pi pi-sparkles",
        disabled: true,
    },
    {
        labelKey: "navigation.mi_dashboard",
        icon: "pi pi-chart-pie",
        href: route("admin.intelligence.dashboard"),
    },
    {
        labelKey: "navigation.bottlenecks",
        icon: "pi pi-exclamation-triangle",
        href: route("admin.intelligence.bottlenecks"),
    },
    {
        labelKey: "navigation.material_forecast",
        icon: "pi pi-box",
        href: route("admin.intelligence.material-forecast"),
    },
    {
        labelKey: "navigation.supplier_performance",
        icon: "pi pi-truck",
        href: route("admin.intelligence.supplier-performance"),
    },
    {
        labelKey: "navigation.quality_trends",
        icon: "pi pi-chart-line",
        href: route("admin.intelligence.quality-trends"),
    },
    {
        labelKey: "navigation.production_risks",
        icon: "pi pi-shield",
        href: route("admin.intelligence.risks"),
    },
    {
        labelKey: "navigation.recommendations",
        icon: "pi pi-lightbulb",
        href: route("admin.intelligence.recommendations"),
    },
]);

const normalizePath = (url) => {
    const path = String(url || "/").split(/[?#]/)[0];

    return path.length > 1 ? path.replace(/\/+$/, "") : path;
};

const currentPath = computed(() => normalizePath(page.url));

const activeSidebarHref = computed(() => {
    return sidebarItems.value
        .filter((item) => !item.disabled && item.href)
        .map((item) => normalizePath(item.href))
        .filter(
            (href) =>
                currentPath.value === href || currentPath.value.startsWith(`${href}/`)
        )
        .sort((first, second) => second.length - first.length)[0];
});

const isSidebarItemActive = (item) => {
    return !item.disabled && normalizePath(item.href) === activeSidebarHref.value;
};

const sidebarLabel = (item) => trans(item.labelKey);

const toggleUserMenu = (event) => {
    userMenu.value.toggle(event);
};
</script>

<template>
    <div
        class="flex h-screen min-h-0 flex-col overflow-hidden bg-slate-50 text-slate-900"
    >
        <header class="z-20 shrink-0 border-b border-slate-200 bg-white">
            <div class="flex h-14 items-center justify-between px-4 sm:px-6">
                <Link
                    :href="route('admin.dashboard')"
                    class="flex items-center gap-3 font-semibold"
                >
                    <span
                        class="grid h-8 w-8 place-items-center rounded bg-blue-600 text-sm text-white"
                        >KM</span
                    >
                    <span>KM Production</span>
                </Link>

                <div class="flex items-center gap-3">
                    <TopbarLocaleSwitch />
                    <div class="hidden text-right text-sm sm:block">
                        <div class="font-medium">{{ user?.name }}</div>
                        <div class="text-xs text-slate-500">
                            {{ user?.email }}
                        </div>
                    </div>
                    <Button
                        type="button"
                        text
                        rounded
                        aria-label="Open user menu"
                        @click="toggleUserMenu"
                    >
                        <Avatar :label="user?.name?.slice(0, 1) || 'U'" shape="circle" />
                    </Button>
                    <Menu ref="userMenu" :model="menuItems" popup />
                </div>
            </div>
        </header>

        <div
            class="grid min-h-0 flex-1 grid-cols-1 grid-rows-[auto_minmax(0,1fr)] overflow-hidden md:grid-cols-[15rem_minmax(0,1fr)] md:grid-rows-1"
        >
            <aside
                class="min-h-0 overflow-hidden border-b border-slate-200 bg-white md:h-full md:border-r md:border-b-0"
            >
                <nav
                    class="flex gap-1 overflow-x-auto overflow-y-hidden p-3 md:block md:h-full md:overflow-x-hidden md:overflow-y-auto"
                >
                    <component
                        :is="item.disabled ? 'span' : Link"
                        v-for="item in sidebarItems"
                        :key="item.labelKey"
                        :href="item.disabled ? undefined : item.href"
                        class="flex min-w-fit items-center gap-2 rounded px-3 py-2 text-sm font-medium"
                        :class="
                            item.disabled
                                ? 'cursor-not-allowed text-slate-400'
                                : isSidebarItemActive(item)
                                ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100'
                                : 'text-slate-700 hover:bg-slate-100'
                        "
                        :aria-current="isSidebarItemActive(item) ? 'page' : undefined"
                    >
                        <i :class="item.icon" />
                        <span>{{ sidebarLabel(item) }}</span>
                    </component>
                </nav>
            </aside>

            <main class="min-h-0 min-w-0 overflow-x-hidden overflow-y-auto p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
