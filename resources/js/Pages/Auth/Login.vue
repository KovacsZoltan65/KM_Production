<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { route } from "@/Utils/routes";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {boolean} canResetPassword A(z) canResetPassword bemeneti értéke.
 * @property {string|null} status A(z) status bemeneti értéke.
 */
/** @type {Props} */
defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login.store"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head :title="$t('auth.login.title')" />

    <GuestLayout>
        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ $t("auth.login.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("auth.login.subtitle") }}
                </p>
            </div>

            <p
                v-if="status"
                class="rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-700"
            >
                {{ status }}
            </p>

            <div class="space-y-2">
                <label for="email" class="text-sm font-medium">{{
                    $t("fields.email")
                }}</label>
                <InputText
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full"
                    autofocus
                    autocomplete="username"
                />
                <p v-if="form.errors.email" class="text-sm text-red-600">
                    {{ form.errors.email }}
                </p>
            </div>

            <div class="space-y-2">
                <label for="password" class="text-sm font-medium">{{
                    $t("fields.password")
                }}</label>
                <InputText
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full"
                    autocomplete="current-password"
                />
                <p v-if="form.errors.password" class="text-sm text-red-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model="form.remember" input-id="remember" binary />
                <span>{{ $t("fields.remember_me") }}</span>
            </label>

            <div class="flex items-center justify-between gap-3">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-blue-700 hover:text-blue-900"
                >
                    {{ $t("auth.forgot_password.link") }}
                </Link>
                <Button
                    type="submit"
                    :label="$t('auth.login.submit')"
                    icon="pi pi-sign-in"
                    :loading="form.processing"
                />
            </div>
        </form>
    </GuestLayout>
</template>
