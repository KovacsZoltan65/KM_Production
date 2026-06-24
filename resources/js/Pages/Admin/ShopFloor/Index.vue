<script setup>
import ShopFloorTaskCard from '@/Components/ShopFloorTaskCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ tasks: Array });
</script>

<template>
    <Head title="Shop Floor" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Shop Floor</h1>
                <p class="text-sm text-slate-600">Ready, active, and inspection-waiting production tasks.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <ShopFloorTaskCard v-for="task in tasks" :key="task.id" :task="task" />
            </div>
        </div>
    </AdminLayout>
</template>
