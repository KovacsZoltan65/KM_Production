<script setup>
import ShopFloorTaskCard from '@/Components/ShopFloorTaskCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ tasks: Array });
</script>

<template>
    <Head title="My Tasks" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">My Tasks</h1>
                <p class="text-sm text-slate-600">Production tasks assigned to your employee profile.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <ShopFloorTaskCard v-for="task in tasks" :key="task.id" :task="task" />
            </div>
        </div>
    </AdminLayout>
</template>
