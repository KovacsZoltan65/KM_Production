<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    icon: { type: String, default: 'pi pi-chart-bar' },
    tone: { type: String, default: 'blue' },
});
</script>

<template>
    <section class="rounded border border-slate-200 bg-white p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-sm font-medium text-slate-500">{{ label }}</div>
                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ value }}</div>
            </div>
            <div
                class="grid h-10 w-10 place-items-center rounded text-lg"
                :class="{
                    'bg-blue-50 text-blue-700': tone === 'blue',
                    'bg-emerald-50 text-emerald-700': tone === 'green',
                    'bg-amber-50 text-amber-700': tone === 'amber',
                    'bg-rose-50 text-rose-700': tone === 'rose',
                    'bg-slate-100 text-slate-700': tone === 'slate',
                }"
            >
                <i :class="icon" />
            </div>
        </div>
    </section>
</template>
