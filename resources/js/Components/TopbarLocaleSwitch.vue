<script setup>
import LocaleSelector from "@/Components/LocaleSelector.vue";
import { usePreferences } from "@/Composables/usePreferences";
import { trans } from "laravel-vue-i18n";
import { computed, ref, watch } from "vue";

const { availableLocales, locale, setLocale } = usePreferences();
const selectedLocale = ref(locale.value);

const localeOptions = computed(() =>
    availableLocales.value.map((option) => ({
        ...option,
        label: trans(`common.locales.${option.value}`),
    }))
);

watch(locale, (value) => {
    selectedLocale.value = value;
});

const changeLocale = async (value) => {
    selectedLocale.value = value;
    await setLocale(value);
};
</script>

<template>
    <div class="flex items-center gap-2">
        <span
            class="hidden text-xs font-medium uppercase tracking-wide text-slate-500 sm:inline"
        >
            {{ $t("common.language") }}
        </span>
        <LocaleSelector
            v-model="selectedLocale"
            :options="localeOptions"
            :placeholder="trans('common.select_language')"
            class="w-36"
            @change="changeLocale"
        />
    </div>
</template>
