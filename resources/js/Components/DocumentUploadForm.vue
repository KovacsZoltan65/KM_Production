<script setup>
import { route } from "@/Utils/routes";
import { useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import Select from "primevue/select";
import Textarea from "primevue/textarea";

const props = defineProps({
    documentTypeOptions: { type: Array, required: true },
    documentableTypeOptions: { type: Array, required: true },
});

const emit = defineEmits(["uploaded", "cancel"]);

const form = useForm({
    file: null,
    document_type: null,
    documentable_type: null,
    documentable_id: null,
    title: "",
    notes: "",
});

const pickFile = (event) => {
    form.file = event.target.files?.[0] || null;
};

const submit = () => {
    form.post(route("admin.documents.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit("uploaded");
        },
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="space-y-1">
                <label class="text-sm font-medium text-slate-700">{{
                    $t("fields.type")
                }}</label>
                <Select
                    v-model="form.document_type"
                    :options="props.documentTypeOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('documents.placeholders.select_type')"
                    class="w-full"
                />
                <Message v-if="form.errors.document_type" severity="error" size="small">{{
                    form.errors.document_type
                }}</Message>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-slate-700">{{
                    $t("fields.linked_entity")
                }}</label>
                <Select
                    v-model="form.documentable_type"
                    :options="props.documentableTypeOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('documents.placeholders.select_entity')"
                    class="w-full"
                />
                <Message
                    v-if="form.errors.documentable_type"
                    severity="error"
                    size="small"
                    >{{ form.errors.documentable_type }}</Message
                >
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-[12rem_1fr]">
            <div class="space-y-1">
                <label class="text-sm font-medium text-slate-700">{{
                    $t("fields.entity_id")
                }}</label>
                <InputNumber
                    v-model="form.documentable_id"
                    input-class="w-full"
                    class="w-full"
                    :min="1"
                />
                <Message
                    v-if="form.errors.documentable_id"
                    severity="error"
                    size="small"
                    >{{ form.errors.documentable_id }}</Message
                >
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-slate-700">{{
                    $t("fields.title")
                }}</label>
                <InputText v-model="form.title" class="w-full" />
                <Message v-if="form.errors.title" severity="error" size="small">{{
                    form.errors.title
                }}</Message>
            </div>
        </div>

        <div class="space-y-1">
            <label class="text-sm font-medium text-slate-700">{{
                $t("fields.file")
            }}</label>
            <input
                type="file"
                class="block w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm"
                @change="pickFile"
            />
            <Message v-if="form.errors.file" severity="error" size="small">{{
                form.errors.file
            }}</Message>
        </div>

        <div class="space-y-1">
            <label class="text-sm font-medium text-slate-700">{{
                $t("fields.notes")
            }}</label>
            <Textarea v-model="form.notes" rows="4" class="w-full" />
            <Message v-if="form.errors.notes" severity="error" size="small">{{
                form.errors.notes
            }}</Message>
        </div>

        <div class="flex justify-end gap-2">
            <Button
                type="button"
                :label="$t('actions.cancel')"
                severity="secondary"
                outlined
                @click="$emit('cancel')"
            />
            <Button
                type="submit"
                :label="$t('actions.upload')"
                icon="pi pi-upload"
                :loading="form.processing"
            />
        </div>
    </form>
</template>
