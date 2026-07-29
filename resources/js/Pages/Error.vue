<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { computed } from "vue";

const props = defineProps({
    status: { type: Number, required: true },
});

const page = usePage();
const layout = computed(() =>
    page.props.auth?.user ? AdminLayout : GuestLayout,
);
const knownStatuses = [403, 404, 419, 429, 500, 503];
const safeStatus = computed(() =>
    knownStatuses.includes(props.status) ? props.status : 500,
);
const title = computed(() => trans(`errors.${safeStatus.value}.title`));
const description = computed(() =>
    trans(`errors.${safeStatus.value}.description`),
);
const reload = () => window.location.reload();
</script>

<template>
    <Head :title="title" />

    <component :is="layout">
        <section
            class="mx-auto max-w-xl rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm"
            data-test="error-page"
        >
            <p class="text-sm font-semibold text-blue-700">
                {{ safeStatus }}
            </p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">
                {{ title }}
            </h1>
            <p class="mt-3 text-slate-600">
                {{ description }}
            </p>
            <Button
                class="mt-6"
                :label="$t('actions.reload')"
                icon="pi pi-refresh"
                data-test="reload"
                @click="reload"
            />
        </section>
    </component>
</template>
