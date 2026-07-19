import "./bootstrap";
import "../css/app.css";
import "primeicons/primeicons.css";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import ConfirmationService from "primevue/confirmationservice";
import ToastService from "primevue/toastservice";
import { route } from "@/Utils/routes";
import { formatPageTitle } from "@/Utils/pageTitle";
import { i18nVue } from "laravel-vue-i18n";

const appName = import.meta.env.VITE_APP_NAME || "KM Production";

createInertiaApp({
    title: (title) => formatPageTitle(title, appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18nVue, {
                locale:
                    props?.initialPage?.props?.preferences?.locale ||
                    document.documentElement.getAttribute("lang") ||
                    "hu",
                fallbackLocale: "hu",
                resolve: async (lang) => {
                    const messages = import.meta.glob("../../lang/*.json");
                    return await messages[`../../lang/${lang}.json`]();
                },
            })
            .use(ToastService)
            .use(ConfirmationService)
            .use(PrimeVue, {
                locale: {
                    firstDayOfWeek: 1,
                },
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: false,
                    },
                },
            })
            .provide("route", route)
            .mount(el);
    },
    progress: {
        color: "#2563eb",
    },
});
