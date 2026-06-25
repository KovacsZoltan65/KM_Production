<script setup>
import DocumentPreviewCard from '@/Components/DocumentPreviewCard.vue';
import DocumentVersionHistory from '@/Components/DocumentVersionHistory.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { onMounted, watch } from 'vue';

const props = defineProps({
    document: { type: Object, required: true },
    versions: { type: Array, required: true },
});

const page = usePage();
const toast = useToast();
const form = useForm({
    title: props.document.title || '',
    notes: props.document.description || '',
});

const save = () => {
    form.patch(route('admin.documents.update', props.document.id), { preserveScroll: true });
};

const approve = () => {
    router.patch(route('admin.documents.approve', props.document.id), {}, { preserveScroll: true });
};

const makeCurrent = () => {
    router.patch(route('admin.documents.make-current', props.document.id), {}, { preserveScroll: true });
};

const destroy = () => {
    router.delete(route('admin.documents.destroy', props.document.id));
};

const flash = (message) => message && toast.add({ severity: 'success', summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head :title="document.title" />
    <AdminLayout>
        <Toast />
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link :href="route('admin.documents.index')" class="text-sm text-blue-700 hover:underline">Document Library</Link>
                    <h1 class="mt-1 text-2xl font-semibold">{{ document.title }}</h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a :href="route('admin.documents.download', document.id)" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Download
                    </a>
                    <Button v-if="!document.approved" type="button" label="Approve" icon="pi pi-check" outlined @click="approve" />
                    <Button v-if="!document.is_current" type="button" label="Make current" icon="pi pi-check-circle" outlined @click="makeCurrent" />
                    <Button type="button" label="Delete" icon="pi pi-trash" severity="danger" outlined @click="destroy" />
                </div>
            </div>

            <DocumentPreviewCard :document="document" />

            <section class="rounded border border-slate-200 bg-white p-4">
                <h2 class="font-semibold">Document Notes</h2>
                <form class="mt-3 space-y-3" @submit.prevent="save">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-slate-700">Title</label>
                        <InputText v-model="form.title" class="w-full" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-slate-700">Notes</label>
                        <Textarea v-model="form.notes" rows="4" class="w-full" />
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" label="Save" icon="pi pi-save" :loading="form.processing" />
                    </div>
                </form>
            </section>

            <DocumentVersionHistory :versions="versions" />
        </div>
    </AdminLayout>
</template>
