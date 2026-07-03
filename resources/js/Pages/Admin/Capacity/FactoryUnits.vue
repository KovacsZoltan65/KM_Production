<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import FactoryLoadTable from "@/Components/FactoryLoadTable.vue";

defineOptions({ layout: AdminLayout });

defineProps({
    loads: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="space-y-5">
        <div>
            <h1 class="text-2xl font-semibold">
                {{ $t("capacity.factory_units.title") }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ $t("capacity.factory_units.subtitle") }}
            </p>
        </div>
        <FactoryLoadTable :loads="loads" />
    </section>
</template>
