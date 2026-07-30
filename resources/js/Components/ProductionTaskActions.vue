<script setup>
import { route } from "@/Utils/routes";
import { router } from "@inertiajs/vue3";
import Button from "primevue/button";
import { ref } from "vue";

/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {{ id: number, status?: string }} task A műveletekhez tartozó gyártási feladat.
 * @property {boolean} dense A(z) dense bemeneti értéke.
 */
/** @type {Props} */
const props = defineProps({ task: Object, dense: Boolean });
const pendingAction = ref(null);

const run = (action, routeName) => {
    if (pendingAction.value) {
        return;
    }

    pendingAction.value = action;
    router.patch(
        route(routeName, props.task.id),
        {},
        {
            onFinish: () => {
                pendingAction.value = null;
            },
        },
    );
};
const start = () => run("start", "admin.production-tasks.start");
const finish = () => run("finish", "admin.production-tasks.finish");
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Button
            v-if="task.status === 'ready'"
            type="button"
            :label="$t('actions.start')"
            icon="pi pi-play"
            :size="dense ? 'small' : undefined"
            :loading="pendingAction === 'start'"
            :disabled="Boolean(pendingAction)"
            @click="start"
        />
        <Button
            v-if="task.status === 'in_progress'"
            type="button"
            :label="$t('actions.finish')"
            icon="pi pi-check"
            severity="success"
            :size="dense ? 'small' : undefined"
            :loading="pendingAction === 'finish'"
            :disabled="Boolean(pendingAction)"
            @click="finish"
        />
    </div>
</template>
