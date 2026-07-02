<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { route } from "@/Utils/routes";
import { Head, useForm } from "@inertiajs/vue3";
import Button from "primevue/button";

defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};
</script>

<template>
    <Head :title="$t('auth.verify_email.title')" />

    <GuestLayout>
        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <h1 class="text-xl font-semibold">{{ $t("auth.verify_email.title") }}</h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("auth.verify_email.subtitle") }}
                </p>
            </div>

            <p
                v-if="status === 'verification-link-sent'"
                class="rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-700"
            >
                {{ $t("auth.verify_email.sent") }}
            </p>

            <Button
                type="submit"
                :label="$t('auth.verify_email.resend')"
                icon="pi pi-send"
                :loading="form.processing"
            />
        </form>
    </GuestLayout>
</template>
