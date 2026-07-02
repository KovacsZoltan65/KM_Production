import { router, usePage } from "@inertiajs/vue3";
import { loadLanguageAsync } from "laravel-vue-i18n";
import { computed } from "vue";
import { route } from "@/Utils/routes";

export function usePreferences() {
    const page = usePage();

    const availableLocales = computed(() => {
        const locales = page.props.preferences?.availableLocales;

        return Array.isArray(locales) && locales.length > 0
            ? locales
            : [
                  { label: "Magyar", value: "hu" },
                  { label: "English", value: "en" },
              ];
    });

    const locale = computed(
        () =>
            page.props.preferences?.locale ||
            document.documentElement.getAttribute("lang") ||
            "hu",
    );

    const setHtmlLang = (value) => {
        document.documentElement.setAttribute("lang", value);
    };

    const setLocale = async (value) => {
        await loadLanguageAsync(value);
        setHtmlLang(value);

        router.post(
            route("preferences.locale"),
            { locale: value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setHtmlLang(value);
                },
            },
        );
    };

    return { availableLocales, locale, setLocale };
}
