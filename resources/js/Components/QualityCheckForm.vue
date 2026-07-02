<script setup>
import { route } from "@/Utils/routes";
import { useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import Select from "primevue/select";
import Textarea from "primevue/textarea";

const props = defineProps({
    productionTask: Object,
    employeeOptions: Array,
    qualityResultOptions: Array,
});

const form = useForm({
    checked_by: null,
    result: "accepted",
    notes: "",
});

const submit = () => {
    form.post(
        route("admin.production-tasks.quality-checks.store", props.productionTask.id),
        {
            preserveScroll: true,
            onSuccess: () => form.reset("notes"),
        }
    );
};
</script>

<template>
    <form
        class="grid gap-3 rounded border border-slate-200 bg-white p-4 md:grid-cols-2"
        @submit.prevent="submit"
    >
        <Select
            v-model="form.checked_by"
            :options="employeeOptions"
            option-label="label"
            option-value="id"
            :placeholder="$t('fields.inspector')"
            filter
        />
        <Select
            v-model="form.result"
            :options="qualityResultOptions"
            option-label="label"
            option-value="value"
            :placeholder="$t('fields.result')"
        />
        <Textarea
            v-model="form.notes"
            rows="2"
            :placeholder="$t('fields.notes')"
            class="md:col-span-2"
        />
        <div class="md:col-span-2">
            <Button
                type="submit"
                :label="$t('quality.record_check')"
                icon="pi pi-verified"
                severity="success"
                :loading="form.processing"
            />
        </div>
    </form>
</template>
