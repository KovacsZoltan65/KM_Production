<script setup>
import ProductionTaskActions from "@/Components/ProductionTaskActions.vue";
import ProductionTaskStatusBadge from "@/Components/ProductionTaskStatusBadge.vue";
import { route } from "@/Utils/routes";
import { Link } from "@inertiajs/vue3";

defineProps({ task: Object });
</script>

<template>
    <article class="rounded border border-slate-200 bg-white p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <Link
                    :href="route('admin.production-tasks.show', task.id)"
                    class="font-semibold text-blue-700 hover:underline"
                >
                    {{
                        task.item_instance?.serial_number ||
                        $t("production.tasks.task_fallback", { id: task.id })
                    }}
                </Link>
                <p class="mt-1 text-sm text-slate-600">
                    {{ task.production_order?.order_number }}
                </p>
            </div>
            <ProductionTaskStatusBadge :status="task.status" />
        </div>
        <div class="mt-4 grid gap-2 text-sm text-slate-700">
            <div>
                <span class="text-slate-500"
                    >{{ $t("fields.operation") }}:</span
                >
                {{ task.operation_sequence_step?.operation_type?.name }}
            </div>
            <div>
                <span class="text-slate-500"
                    >{{ $t("fields.factory_unit") }}:</span
                >
                {{ task.operation_sequence_step?.factory_unit?.code }}
            </div>
            <div>
                <span class="text-slate-500">{{ $t("fields.employee") }}:</span>
                {{ task.employee?.name }}
            </div>
        </div>
        <div class="mt-4">
            <ProductionTaskActions :task="task" dense />
        </div>
    </article>
</template>
