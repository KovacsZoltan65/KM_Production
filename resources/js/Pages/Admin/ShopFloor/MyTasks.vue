<script setup>
import ShopFloorTaskCard from "@/Components/ShopFloorTaskCard.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

defineProps({ tasks: Array });
</script>

<template>
    <Head :title="$t('shop_floor.my_tasks.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("shop_floor.my_tasks.title") }}
                </h1>
                <p class="text-sm text-slate-600">
                    {{ $t("shop_floor.my_tasks.subtitle") }}
                </p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <ShopFloorTaskCard
                    v-for="task in tasks"
                    :key="task.id"
                    :task="task"
                />
            </div>
        </div>
    </AdminLayout>
</template>
