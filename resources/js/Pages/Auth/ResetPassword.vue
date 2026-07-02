<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { route } from "@/Utils/routes";
import { Head, useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("password.store"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head :title="$t('auth.reset_password.title')" />

    <GuestLayout>
        <form class="space-y-5" @submit.prevent="submit">
            <h1 class="text-xl font-semibold">{{ $t("auth.reset_password.title") }}</h1>

            <div class="space-y-2">
                <label for="email" class="text-sm font-medium">{{
                    $t("fields.email")
                }}</label>
                <InputText
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full"
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
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password" class="text-sm text-red-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-medium">{{
                    $t("fields.confirm_password")
                }}</label>
                <InputText
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full"
                    autocomplete="new-password"
                />
            </div>

            <Button
                type="submit"
                :label="$t('auth.reset_password.submit')"
                icon="pi pi-key"
                :loading="form.processing"
            />
        </form>
    </GuestLayout>
</template>
