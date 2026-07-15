<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, Link } from "@inertiajs/vue3";
import Button from "primevue/button";
import Card from "primevue/card";

/**
 * A készletmodul navigációs kártyája.
 * @typedef {Object} InventorySection
 * @property {string} titleKey A cím fordítási kulcsa.
 * @property {string} descriptionKey A leírás fordítási kulcsa.
 * @property {string} icon A PrimeIcons osztályneve.
 * @property {string} routeName A cél útvonal neve.
 */

/** @type {InventorySection[]} */
const sections = [
    {
        titleKey: "inventory.dashboard.stock_balances",
        descriptionKey: "inventory.stock_balances.subtitle",
        icon: "pi pi-box",
        routeName: "admin.inventory.stock-balances.index",
    },
    {
        titleKey: "inventory.dashboard.stock_movements",
        descriptionKey: "inventory.stock_movements.subtitle",
        icon: "pi pi-arrow-right-arrow-left",
        routeName: "admin.inventory.stock-movements.index",
    },
    {
        titleKey: "inventory.dashboard.stock_reservations",
        descriptionKey: "inventory.stock_reservations.subtitle",
        icon: "pi pi-lock",
        routeName: "admin.inventory.stock-reservations.index",
    },
    {
        titleKey: "inventory.dashboard.material_requirements",
        descriptionKey: "inventory.material_requirements.subtitle",
        icon: "pi pi-list",
        routeName: "admin.inventory.material-requirements.index",
    },
    {
        titleKey: "inventory.dashboard.shortages",
        descriptionKey: "inventory.shortages.subtitle",
        icon: "pi pi-exclamation-triangle",
        routeName: "admin.inventory.shortages.index",
    },
];
</script>

<template>
    <Head :title="$t('inventory.dashboard.title')" />

    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ $t("inventory.dashboard.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("inventory.dashboard.description") }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="section in sections"
                    :key="section.routeName"
                    class="min-w-0"
                >
                    <template #title>
                        <div class="flex items-center gap-3">
                            <span
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-700"
                            >
                                <i :class="section.icon" />
                            </span>
                            <span>{{ $t(section.titleKey) }}</span>
                        </div>
                    </template>
                    <template #content>
                        <p class="text-sm text-slate-600">
                            {{ $t(section.descriptionKey) }}
                        </p>
                    </template>
                    <template #footer>
                        <Link
                            :href="route(section.routeName)"
                            class="inline-flex"
                        >
                            <Button
                                as="span"
                                :label="$t('inventory.dashboard.open')"
                                icon="pi pi-arrow-right"
                                icon-pos="right"
                            />
                        </Link>
                    </template>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
