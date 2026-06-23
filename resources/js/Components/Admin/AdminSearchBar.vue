<script setup>
import Button from "primevue/button";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import Select from "primevue/select";

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
        <IconField class="w-full flex-1">
            <InputIcon class="pi pi-search" />
            <InputText
                :model-value="modelValue"
                class="w-full"
                placeholder="Search"
                @update:model-value="$emit('update:modelValue', $event)"
                @keydown.enter="$emit('search')"
            />
        </IconField>

        <!-- Sor / Page -->
        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-600">Per page</span>
            <Select
                :model-value="perPage"
                :options="perPageOptions"
                class="w-24"
                @update:model-value="$emit('update:perPage', $event)"
            />
        </div>

        <!-- Keresés gomb -->
        <Button
            type="button"
            label="Search"
            icon="pi pi-search"
            outlined
            @click="$emit('search')"
        />
    </div>
</template>
