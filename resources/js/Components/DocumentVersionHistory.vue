<script setup>
import DocumentStatusBadge from "@/Components/DocumentStatusBadge.vue";
import { route } from "@/Utils/routes";
import { Link, router, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import { computed } from "vue";

/**
 * Dokumentumverzió a verzióelőzményben.
 * @typedef {Object} DocumentVersion
 * @property {number} id A dokumentum azonosítója.
 * @property {number} version A verziószám.
 * @property {string|null} original_filename Az eredeti fájlnév.
 * @property {boolean} is_current Jelzi az aktuális verziót.
 * @property {boolean} approved Jelzi a jóváhagyást.
 * @property {string|null} created_at A létrehozás időpontja.
 * @property {{name: string}|null} uploader A feltöltő felhasználó.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {DocumentVersion[]} versions A dokumentum verzióelőzménye.
 */
/** @type {Props} */
const props = defineProps({
    versions: { type: Array, required: true },
});
const page = usePage();
const canMakeCurrent = computed(
    () =>
        page.props.auth?.roles?.includes("super-admin") ||
        page.props.auth?.permissions?.includes("documents.version"),
);

const makeCurrent = (document) => {
    router.patch(
        route("admin.documents.make-current", document.id),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <section class="rounded border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="font-semibold">{{ $t("documents.version_history") }}</h2>
        </div>
        <div class="divide-y divide-slate-100">
            <div
                v-for="document in props.versions"
                :key="document.id"
                class="flex flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <Link
                        :href="route('admin.documents.show', document.id)"
                        class="font-medium text-blue-700 hover:underline"
                    >
                        v{{ document.version }} -
                        {{ document.original_filename }}
                    </Link>
                    <div class="mt-1 text-sm text-slate-600">
                        {{ document.uploader?.name || "-" }} ·
                        {{
                            document.created_at
                                ? String(document.created_at).slice(0, 16)
                                : "-"
                        }}
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <DocumentStatusBadge :document="document" />
                    <Button
                        v-if="canMakeCurrent && !document.is_current"
                        type="button"
                        :label="$t('actions.make_current')"
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
