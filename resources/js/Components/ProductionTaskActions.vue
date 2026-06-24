<script setup>
import { route } from '@/Utils/routes';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';

const props = defineProps({ task: Object, dense: Boolean });

const start = () => router.patch(route('admin.production-tasks.start', props.task.id));
const finish = () => router.patch(route('admin.production-tasks.finish', props.task.id));
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Button
            v-if="task.status === 'ready'"
            type="button"
            label="Start"
            icon="pi pi-play"
            :size="dense ? 'small' : undefined"
            @click="start"
        />
        <Button
            v-if="task.status === 'in_progress'"
            type="button"
            label="Finish"
            icon="pi pi-check"
            severity="success"
            :size="dense ? 'small' : undefined"
            @click="finish"
        />
    </div>
</template>
