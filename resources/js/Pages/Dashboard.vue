<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

/**
 * A közös jogosultsági Inertia propok.
 * @typedef {Object} SharedAuth
 * @property {string[]} roles A felhasználó szerepkörei.
 * @property {string[]} permissions A felhasználó jogosultságai.
 */

const page = usePage();
/** @type {import('vue').ComputedRef<string[]>} */
const roles = computed(() => page.props.auth?.roles || []);
/** @type {import('vue').ComputedRef<string[]>} */
const permissions = computed(() => page.props.auth?.permissions || []);
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("dashboard.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("dashboard.subtitle") }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <section class="rounded border border-slate-200 bg-white p-4">
                    <h2
                        class="text-sm font-semibold uppercase tracking-wide text-slate-500"
                    >
                        {{ $t("fields.roles") }}
                    </h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="role in roles"
                            :key="role"
                            class="rounded bg-blue-50 px-2.5 py-1 text-sm font-medium text-blue-700"
                        >
                            {{ role }}
                        </span>
                        <span
                            v-if="roles.length === 0"
                            class="text-sm text-slate-500"
                            >{{ $t("dashboard.no_roles_assigned") }}</span
                        >
                    </div>
                </section>

                <section class="rounded border border-slate-200 bg-white p-4">
                    <h2
                        class="text-sm font-semibold uppercase tracking-wide text-slate-500"
                    >
                        {{ $t("fields.permissions") }}
                    </h2>
                    <p class="mt-3 text-3xl font-semibold">
                        {{ permissions.length }}
                    </p>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
