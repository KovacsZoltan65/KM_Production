<script setup>
import Button from "primevue/button";
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
        <div class="relative w-full flex-1">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
            <InputText
                :model-value="modelValue"
                class="w-full pl-10"
                :placeholder="trans('common.search')"
                @update:model-value="$emit('update:modelValue', $event)"
                @keydown.enter="$emit('search')"
            />
        </div>

        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-600">{{ $t("common.per_page") }}</span>
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
