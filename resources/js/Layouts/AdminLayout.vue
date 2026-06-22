<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
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
        command: () => router.visit('/profile'),
    },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: () => router.post('/logout'),
    },
];

const sidebarItems = [
    { label: 'Dashboard', icon: 'pi pi-home', href: '/dashboard' },
    { label: 'Users', icon: 'pi pi-users', href: '/admin/users' },
    { label: 'Roles', icon: 'pi pi-shield', href: '/admin/roles' },
    { label: 'Permissions', icon: 'pi pi-key', href: '/admin/permissions' },
    { label: 'Employees', icon: 'pi pi-id-card', href: '/admin/employees' },
    { label: 'Factory Units', icon: 'pi pi-building', href: '/admin/factory-units' },
    { label: 'Locations', icon: 'pi pi-map-marker', href: '/admin/locations' },
    { label: 'Professional Roles', icon: 'pi pi-briefcase', href: '/admin/professional-roles' },
];

const toggleUserMenu = (event) => {
    userMenu.value.toggle(event);
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
            <div class="flex h-14 items-center justify-between px-4 sm:px-6">
                <Link href="/dashboard" class="flex items-center gap-3 font-semibold">
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
