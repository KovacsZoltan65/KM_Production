import { beforeEach, describe, expect, it, vi } from "vitest";
import { nextTick } from "vue";
import { loadLanguageAsync } from "laravel-vue-i18n";
import { usePreferences } from "@/Composables/usePreferences";
import {
    inertiaPage,
    inertiaRouter,
} from "../mocks/inertia.js";

describe("usePreferences", () => {
    beforeEach(() => {
        vi.mocked(loadLanguageAsync).mockClear();
    });

    it("a szerverről kapott nyelvet és választható nyelveket adja vissza", () => {
        inertiaPage.props.preferences = {
            locale: "en",
            availableLocales: [{ value: "hu" }, { value: "en" }],
        };

        const preferences = usePreferences();

        expect(preferences.locale.value).toBe("en");
        expect(preferences.availableLocales.value).toEqual([
            { value: "hu" },
            { value: "en" },
        ]);
    });

    it("hiányos page props esetén biztonságos alapértékeket használ", () => {
        inertiaPage.props.preferences = {};
        document.documentElement.lang = "";

        const preferences = usePreferences();

        expect(preferences.locale.value).toBe("hu");
        expect(preferences.availableLocales.value).toEqual([
            { value: "hu" },
            { value: "en" },
        ]);
    });

    it("nyelvváltáskor betölti a nyelvet, frissíti a dokumentumot és ment", async () => {
        const preferences = usePreferences();

        await preferences.setLocale("en");

        expect(loadLanguageAsync).toHaveBeenCalledWith("en");
        expect(document.documentElement.lang).toBe("en");
        expect(inertiaRouter.post).toHaveBeenCalledWith(
            "/preferences/locale",
            { locale: "en" },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it("sikeres mentési callback után is szinkronban tartja a html nyelvét", async () => {
        const preferences = usePreferences();
        await preferences.setLocale("en");
        document.documentElement.lang = "hu";

        inertiaRouter.post.mock.calls[0][2].onSuccess();
        await nextTick();

        expect(document.documentElement.lang).toBe("en");
    });
});
