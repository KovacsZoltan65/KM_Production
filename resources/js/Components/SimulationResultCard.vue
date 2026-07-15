<script setup>
import Tag from "primevue/tag";

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ criticalFactoryUnit?: string, criticalProfessionalRole?: string, estimatedFinish?: string, estimatedStart?: string, isLate?: boolean, lateByMinutes?: number }} result A kapacitásszimuláció eredménye.
 */
/** @type {Props} */
defineProps({
    result: { type: Object, default: null },
});
</script>

<template>
    <div
        v-if="result"
        class="grid gap-3 rounded border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-3"
    >
        <div>
            <div class="text-xs uppercase text-slate-500">
                {{ $t("capacity.fields.estimated_start") }}
            </div>
            <div class="font-medium">{{ result.estimatedStart }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">
                {{ $t("capacity.fields.estimated_finish") }}
            </div>
            <div class="font-medium">{{ result.estimatedFinish }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">
                {{ $t("capacity.fields.late") }}
            </div>
            <Tag
                :value="
                    result.isLate
                        ? $t('capacity.fields.late_minutes', {
                              minutes: result.lateByMinutes,
                          })
                        : $t('common.no')
                "
                :severity="result.isLate ? 'danger' : 'success'"
            />
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">
                {{ $t("capacity.fields.critical_factory_unit") }}
            </div>
            <div class="font-medium">{{ result.criticalFactoryUnit }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">
                {{ $t("capacity.fields.critical_professional_role") }}
            </div>
            <div class="font-medium">{{ result.criticalProfessionalRole }}</div>
        </div>
    </div>
</template>
