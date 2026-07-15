<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { route } from "@/Utils/routes";
import { Head, useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {string|null} status A(z) status bemeneti értéke.
 */
/** @type {Props} */
defineProps({
    status: String,
});

const form = useForm({
    email: "",
});

const submit = () => {
    form.post(route("password.email"));
};
</script>

<template>
    <Head :title="$t('auth.forgot_password.title')" />

    <GuestLayout>
        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ $t("auth.forgot_password.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("auth.forgot_password.subtitle") }}
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

            <Button
                type="submit"
                :label="$t('auth.forgot_password.submit')"
                icon="pi pi-send"
                :loading="form.processing"
            />
        </form>
    </GuestLayout>
</template>
