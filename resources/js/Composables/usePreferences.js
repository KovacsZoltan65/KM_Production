import { router, usePage } from "@inertiajs/vue3";
import { loadLanguageAsync } from "laravel-vue-i18n";
import { computed } from "vue";
import { route } from "@/Utils/routes";

/**
 * Választható alkalmazásnyelv.
 * @typedef {Object} LocaleOption
 * @property {string} value A nyelv ISO-kódja.
 * @property {string} [label] A nyelv megjelenítendő neve.
 */

/**
 * A felhasználói nyelvi beállítás reaktív állapota és módosító művelete.
 *
 * @returns {{
 *   availableLocales: import('vue').ComputedRef<LocaleOption[]>,
 *   locale: import('vue').ComputedRef<string>,
 *   setLocale: (value: string) => Promise<void>
 * }} A választható nyelvek, az aktív nyelv és a nyelvváltó művelet.
 */
export function usePreferences() {
    const page = usePage();

    const availableLocales = computed(() => {
        const locales = page.props.preferences?.availableLocales;

        return Array.isArray(locales) && locales.length > 0
            ? locales
            : [{ value: "hu" }, { value: "en" }];
    });

    const locale = computed(
        () =>
            page.props.preferences?.locale ||
            document.documentElement.getAttribute("lang") ||
            "hu",
    );

    /**
     * Szinkronban tartja a dokumentum nyelvi attribútumát.
     * @param {string} value A beállítandó nyelvkód.
     * @returns {void}
     */
    const setHtmlLang = (value) => {
        document.documentElement.setAttribute("lang", value);
    };

    /**
     * Betölti és a szerveren is eltárolja a kiválasztott nyelvet.
     * @param {string} value A kiválasztott nyelvkód.
     * @returns {Promise<void>}
     */
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
