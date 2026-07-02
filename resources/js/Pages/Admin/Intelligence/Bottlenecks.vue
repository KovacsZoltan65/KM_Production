<script setup>
import BottleneckTable from "@/Components/BottleneckTable.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

defineProps({ analysis: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('intelligence.bottlenecks.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("intelligence.bottlenecks.title") }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $t("intelligence.bottlenecks.subtitle") }}
                </p>
            </div>
            <BottleneckTable :rows="analysis.rows" />
        </div>
    </AdminLayout>
</template>
