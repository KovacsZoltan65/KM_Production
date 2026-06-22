<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const profileForm = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.patch('/profile', {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put('/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => passwordForm.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Profile" />

    <AdminLayout>
        <div class="max-w-3xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold">Profile</h1>
                <p class="mt-1 text-sm text-slate-600">Manage your account information and password.</p>
            </div>

            <section class="rounded border border-slate-200 bg-white p-5">
                <form class="space-y-4" @submit.prevent="updateProfile">
                    <h2 class="text-base font-semibold">Account information</h2>

                    <div class="space-y-2">
                        <label for="name" class="text-sm font-medium">Name</label>
                        <InputText id="name" v-model="profileForm.name" class="w-full" autocomplete="name" />
                        <p v-if="profileForm.errors.name" class="text-sm text-red-600">{{ profileForm.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium">Email</label>
                        <InputText id="email" v-model="profileForm.email" type="email" class="w-full" autocomplete="username" />
                        <p v-if="profileForm.errors.email" class="text-sm text-red-600">{{ profileForm.errors.email }}</p>
                    </div>

                    <p v-if="mustVerifyEmail && user.email_verified_at === null" class="text-sm text-amber-700">
                        Your email address is unverified.
                    </p>

                    <Button type="submit" label="Save" icon="pi pi-save" :loading="profileForm.processing" />
                </form>
            </section>

            <section class="rounded border border-slate-200 bg-white p-5">
                <form class="space-y-4" @submit.prevent="updatePassword">
                    <h2 class="text-base font-semibold">Password</h2>

                    <div class="space-y-2">
                        <label for="current_password" class="text-sm font-medium">Current password</label>
                        <InputText id="current_password" v-model="passwordForm.current_password" type="password" class="w-full" />
                        <p v-if="passwordForm.errors.current_password" class="text-sm text-red-600">
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="new_password" class="text-sm font-medium">New password</label>
                        <InputText id="new_password" v-model="passwordForm.password" type="password" class="w-full" />
                        <p v-if="passwordForm.errors.password" class="text-sm text-red-600">{{ passwordForm.errors.password }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
                        <InputText
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="w-full"
                        />
                    </div>

                    <Button type="submit" label="Update password" icon="pi pi-key" :loading="passwordForm.processing" />
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
