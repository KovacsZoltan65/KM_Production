<script setup>
import DocumentStatusBadge from '@/Components/DocumentStatusBadge.vue';
import { route } from '@/Utils/routes';
import { Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';

const props = defineProps({
    versions: { type: Array, required: true },
});

const makeCurrent = (document) => {
    router.patch(route('admin.documents.make-current', document.id), {}, { preserveScroll: true });
};
</script>

<template>
    <section class="rounded border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="font-semibold">Version History</h2>
        </div>
        <div class="divide-y divide-slate-100">
            <div
                v-for="document in props.versions"
                :key="document.id"
                class="flex flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <Link :href="route('admin.documents.show', document.id)" class="font-medium text-blue-700 hover:underline">
                        v{{ document.version }} - {{ document.original_filename }}
                    </Link>
                    <div class="mt-1 text-sm text-slate-600">
                        {{ document.uploader?.name || '-' }} · {{ document.created_at ? String(document.created_at).slice(0, 16) : '-' }}
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <DocumentStatusBadge :document="document" />
                    <Button
                        v-if="!document.is_current"
                        type="button"
                        label="Make current"
                        icon="pi pi-check-circle"
                        size="small"
                        outlined
                        @click="makeCurrent(document)"
                    />
                </div>
            </div>
        </div>
    </section>
</template>
