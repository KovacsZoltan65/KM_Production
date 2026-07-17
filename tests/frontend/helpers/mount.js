import { mount } from "@vue/test-utils";
import PrimeVue from "primevue/config";

/**
 * A közös alkalmazáspluginekkel mountolja a komponenst, miközben minden opció
 * tesztenként felülírható marad.
 */
export const mountWithApp = (component, options = {}) => {
    const global = options.global || {};

    return mount(component, {
        ...options,
        global: {
            ...global,
            plugins: [PrimeVue, ...(global.plugins || [])],
            mocks: {
                $t: (key) => key,
                ...(global.mocks || {}),
            },
            provide: {
                route: (name) => `/test-route/${name}`,
                ...(global.provide || {}),
            },
        },
    });
};
