<script setup>
import Tag from 'primevue/tag';

defineProps({
    result: { type: Object, default: null },
});
</script>

<template>
    <div v-if="result" class="grid gap-3 rounded border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <div class="text-xs uppercase text-slate-500">Estimated Start</div>
            <div class="font-medium">{{ result.estimatedStart }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">Estimated Finish</div>
            <div class="font-medium">{{ result.estimatedFinish }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">Late</div>
            <Tag :value="result.isLate ? `Yes, ${result.lateByMinutes} min` : 'No'" :severity="result.isLate ? 'danger' : 'success'" />
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">Critical Factory Unit</div>
            <div class="font-medium">{{ result.criticalFactoryUnit }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">Critical Professional Role</div>
            <div class="font-medium">{{ result.criticalProfessionalRole }}</div>
        </div>
    </div>
</template>
