<script setup>
import Button from "primevue/button";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import { trans } from "laravel-vue-i18n";

defineProps({
    modelValue: { type: String, default: "" },
    perPage: { type: Number, default: 10 },
});

defineEmits(["update:modelValue", "update:perPage", "search"]);

const perPageOptions = [10, 25, 50, 100];
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded border border-slate-200 bg-white p-3 sm:flex-row sm:items-center"
    >
        <div class="w-full flex-1">
            <IconField class="w-full">
                <InputIcon class="pi pi-search text-slate-400" />
                <InputText
                    :model-value="modelValue"
                    class="w-full"
                    :placeholder="trans('common.search')"
                    @update:model-value="$emit('update:modelValue', $event)"
                    @keydown.enter="$emit('search')"
                />
            </IconField>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-600">{{
                $t("common.per_page")
            }}</span>
            <Select
                :model-value="perPage"
                :options="perPageOptions"
                class="w-24"
                @update:model-value="$emit('update:perPage', $event)"
            />
        </div>

        <Button
            type="button"
            :label="trans('actions.search')"
            icon="pi pi-search"
            outlined
            @click="$emit('search')"
        />
    </div>
</template>
