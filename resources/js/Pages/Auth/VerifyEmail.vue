<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { route } from '@/Utils/routes';
import { Head, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';

defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <Head title="Verify Email" />

    <GuestLayout>
        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <h1 class="text-xl font-semibold">Verify email</h1>
                <p class="mt-1 text-sm text-slate-600">A verification link is required before entering the dashboard.</p>
            </div>

            <p v-if="status === 'verification-link-sent'" class="rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                A new verification link has been sent.
            </p>

            <Button type="submit" label="Resend verification email" icon="pi pi-send" :loading="form.processing" />
        </form>
    </GuestLayout>
</template>
