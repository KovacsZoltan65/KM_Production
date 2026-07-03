<script setup>
import { computed } from "vue";

const props = defineProps({
    rows: { type: Array, default: () => [] },
});

const colors = [
    "#2563eb",
    "#059669",
    "#d97706",
    "#dc2626",
    "#7c3aed",
    "#0f766e",
    "#64748b",
];
const total = computed(() =>
    props.rows.reduce((sum, row) => sum + Number(row.value || 0), 0)
);
const segments = computed(() => {
    let offset = 0;

    return props.rows.map((row, index) => {
        const value = Number(row.value || 0);
        const length = total.value > 0 ? (value / total.value) * 100 : 0;
        const segment = {
            ...row,
            color: colors[index % colors.length],
            dasharray: `${length} ${100 - length}`,
            dashoffset: -offset,
        };
        offset += length;

        return segment;
    });
});

const label = (value) => String(value || "").replaceAll("_", " ");
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-[9rem_1fr] sm:items-center">
        <div class="relative h-36 w-36">
            <svg viewBox="0 0 42 42" class="h-full w-full -rotate-90">
                <circle
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="transparent"
                    stroke="#e2e8f0"
                    stroke-width="5"
                />
                <circle
                    v-for="segment in segments"
                    :key="segment.label"
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="transparent"
                    :stroke="segment.color"
                    stroke-width="5"
                    :stroke-dasharray="segment.dasharray"
                    :stroke-dashoffset="segment.dashoffset"
                />
            </svg>
            <div class="absolute inset-0 grid place-items-center text-xl font-semibold">
                {{ total }}
            </div>
        </div>
        <div class="space-y-2">
            <div
                v-for="segment in segments"
                :key="segment.label"
                class="flex items-center justify-between gap-3 text-sm"
            >
                <span class="flex items-center gap-2 capitalize">
                    <span
                        class="h-2.5 w-2.5 rounded-full"
                        :style="{ backgroundColor: segment.color }"
                    />
                    {{ label(segment.label) }}
                </span>
                <span class="font-medium">{{ segment.value }}</span>
            </div>
            <div v-if="segments.length === 0" class="text-sm text-slate-500">
                {{ $t("common.no_data") }}
            </div>
        </div>
    </div>
</template>
