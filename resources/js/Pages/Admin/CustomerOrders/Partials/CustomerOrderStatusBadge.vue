<script setup>
const props = defineProps({
    status: { type: String, required: true },
});

const labels = {
    draft: 'Draft',
    confirmed: 'Confirmed',
    material_planning: 'Material planning',
    waiting_for_material: 'Waiting for material',
    ready_for_production: 'Ready for production',
    in_production: 'In production',
    quality_check: 'Quality check',
    ready_to_ship: 'Ready to ship',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const tones = {
    draft: 'bg-slate-100 text-slate-700',
    confirmed: 'bg-blue-50 text-blue-700',
    material_planning: 'bg-cyan-50 text-cyan-700',
    waiting_for_material: 'bg-amber-50 text-amber-700',
    ready_for_production: 'bg-indigo-50 text-indigo-700',
    in_production: 'bg-violet-50 text-violet-700',
    quality_check: 'bg-fuchsia-50 text-fuchsia-700',
    ready_to_ship: 'bg-teal-50 text-teal-700',
    completed: 'bg-emerald-50 text-emerald-700',
    cancelled: 'bg-red-50 text-red-700',
};
</script>

<template>
    <span class="inline-flex rounded px-2 py-1 text-xs font-medium" :class="tones[props.status] || tones.draft">
        {{ labels[props.status] || props.status }}
    </span>
</template>
