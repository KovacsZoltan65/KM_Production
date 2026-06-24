<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { route } from '@/Utils/routes';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userMenu = ref();

const menuItems = [
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => router.visit(route('profile.edit')),
    },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: () => router.post(route('logout')),
    },
];

const sidebarItems = [
    { label: 'Dashboard', icon: 'pi pi-home', href: route('dashboard') },
    { label: 'Users', icon: 'pi pi-users', href: route('admin.users.index') },
    { label: 'Roles', icon: 'pi pi-shield', href: route('admin.roles.index') },
    { label: 'Permissions', icon: 'pi pi-key', href: route('admin.permissions.index') },
    { label: 'Employees', icon: 'pi pi-id-card', href: route('admin.employees.index') },
    { label: 'Factory Units', icon: 'pi pi-building', href: route('admin.factory-units.index') },
    { label: 'Locations', icon: 'pi pi-map-marker', href: route('admin.locations.index') },
    { label: 'Professional Roles', icon: 'pi pi-briefcase', href: route('admin.professional-roles.index') },
    { label: 'Items', icon: 'pi pi-box', href: route('admin.items.index') },
    { label: 'BOMs', icon: 'pi pi-list-check', href: route('admin.boms.index') },
    { label: 'Operation Types', icon: 'pi pi-cog', href: route('admin.operation-types.index') },
    { label: 'Operation Sequences', icon: 'pi pi-sitemap', href: route('admin.operation-sequences.index') },
    { label: 'Business Partners', icon: 'pi pi-address-book', disabled: true },
    { label: 'Customers', icon: 'pi pi-user-plus', href: route('admin.customers.index') },
    { label: 'Suppliers', icon: 'pi pi-truck', href: route('admin.suppliers.index') },
    { label: 'Sales', icon: 'pi pi-shopping-cart', disabled: true },
    { label: 'Customer Orders', icon: 'pi pi-file-edit', href: route('admin.customer-orders.index') },
    { label: 'Planning', icon: 'pi pi-calendar-clock', disabled: true },
    { label: 'Production Plans', icon: 'pi pi-calendar-plus', href: route('admin.production-plans.index') },
    { label: 'Production', icon: 'pi pi-cog', disabled: true },
    { label: 'Shop Floor', icon: 'pi pi-th-large', href: route('admin.shop-floor.index') },
    { label: 'My Tasks', icon: 'pi pi-list-check', href: route('admin.shop-floor.my-tasks') },
    { label: 'Production Tasks', icon: 'pi pi-play-circle', href: route('admin.production-tasks.index') },
    { label: 'Inventory', icon: 'pi pi-warehouse', disabled: true },
    { label: 'Stock Balances', icon: 'pi pi-box', href: route('admin.inventory.stock-balances.index') },
    { label: 'Stock Movements', icon: 'pi pi-arrow-right-arrow-left', href: route('admin.inventory.stock-movements.index') },
    { label: 'Reservations', icon: 'pi pi-lock', href: route('admin.inventory.stock-reservations.index') },
    { label: 'Material Requirements', icon: 'pi pi-list', href: route('admin.inventory.material-requirements.index') },
    { label: 'Shortages', icon: 'pi pi-exclamation-triangle', href: route('admin.inventory.shortages.index') },
    { label: 'Procurement', icon: 'pi pi-shopping-bag', disabled: true },
    { label: 'Procurement Dashboard', icon: 'pi pi-chart-bar', href: route('admin.procurement.dashboard') },
    { label: 'Purchase Requisitions', icon: 'pi pi-list-check', href: route('admin.purchase-requisitions.index') },
    { label: 'Purchase Orders', icon: 'pi pi-shopping-cart', href: route('admin.purchase-orders.index') },
    { label: 'Goods Receipts', icon: 'pi pi-inbox', href: route('admin.goods-receipts.index') },
];

const toggleUserMenu = (event) => {
    userMenu.value.toggle(event);
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
            <div class="flex h-14 items-center justify-between px-4 sm:px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-3 font-semibold">
                    <span class="grid h-8 w-8 place-items-center rounded bg-blue-600 text-sm text-white">KM</span>
                    <span>KM Production</span>
                </Link>

                <div class="flex items-center gap-3">
                    <div class="hidden text-right text-sm sm:block">
                        <div class="font-medium">{{ user?.name }}</div>
                        <div class="text-xs text-slate-500">{{ user?.email }}</div>
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

        <div class="grid min-h-[calc(100vh-3.5rem)] grid-cols-1 md:grid-cols-[15rem_1fr]">
            <aside class="border-b border-slate-200 bg-white md:border-b-0 md:border-r">
                <nav class="flex gap-1 overflow-x-auto p-3 md:block">
                    <component
                        :is="item.disabled ? 'span' : Link"
                        v-for="item in sidebarItems"
                        :key="item.label"
                        :href="item.disabled ? undefined : item.href"
                        class="flex min-w-fit items-center gap-2 rounded px-3 py-2 text-sm font-medium"
                        :class="item.disabled ? 'cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-100'"
                    >
                        <i :class="item.icon" />
                        <span>{{ item.label }}</span>
                    </component>
                </nav>
            </aside>

            <main class="p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
