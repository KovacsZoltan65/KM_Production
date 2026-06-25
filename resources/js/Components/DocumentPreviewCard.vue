<script setup>
import DocumentStatusBadge from '@/Components/DocumentStatusBadge.vue';

const props = defineProps({
    document: { type: Object, required: true },
});

const sizeLabel = (bytes) => {
    if (!bytes) {
        return '-';
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
};

const typeLabel = (value) => String(value || '').replaceAll('_', ' ');
</script>

<template>
    <section class="rounded border border-slate-200 bg-white p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold">{{ props.document.title }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ props.document.original_filename }}</p>
            </div>
            <DocumentStatusBadge :document="props.document" />
        </div>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-slate-500">Type</dt>
                <dd class="font-medium capitalize">{{ typeLabel(props.document.document_type) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Version</dt>
                <dd class="font-medium">v{{ props.document.version }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Size</dt>
                <dd class="font-medium">{{ sizeLabel(props.document.file_size) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Mime type</dt>
                <dd class="font-medium">{{ props.document.mime_type || '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Linked entity</dt>
                <dd class="font-medium">{{ props.document.documentable_type }} #{{ props.document.documentable_id }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Uploaded by</dt>
                <dd class="font-medium">{{ props.document.uploader?.name || '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Uploaded at</dt>
                <dd class="font-medium">{{ props.document.created_at ? String(props.document.created_at).slice(0, 16) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Approved by</dt>
                <dd class="font-medium">{{ props.document.approver?.name || '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Approved at</dt>
                <dd class="font-medium">{{ props.document.approved_at ? String(props.document.approved_at).slice(0, 16) : '-' }}</dd>
            </div>
        </dl>

        <div class="mt-4">
            <div class="text-sm text-slate-500">Checksum</div>
            <div class="break-all rounded bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">{{ props.document.checksum || '-' }}</div>
        </div>
    </section>
</template>
