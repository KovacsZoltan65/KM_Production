import { defineComponent, h, reactive } from "vue";
import { vi } from "vitest";

export const inertiaPage = reactive({
    url: "/",
    props: {
        auth: { user: null, permissions: [] },
        flash: {},
        preferences: { locale: "hu", availableLocales: [] },
    },
});

export const inertiaRouter = {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    visit: vi.fn(),
    reload: vi.fn(),
};

export const createFormMock = (values = {}) =>
    reactive({
        ...values,
        errors: {},
        processing: false,
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        reset: vi.fn(),
        clearErrors: vi.fn(),
    });

const passthrough = (name, tag = "div") =>
    defineComponent({
        name,
        inheritAttrs: false,
        setup(_, { attrs, slots }) {
            return () => h(tag, attrs, slots.default?.());
        },
    });

export const inertiaModule = {
    router: inertiaRouter,
    usePage: () => inertiaPage,
    useForm: (values) => createFormMock(values),
    Head: passthrough("Head"),
    Link: passthrough("Link", "a"),
};

export const resetInertiaMock = () => {
    Object.values(inertiaRouter).forEach((method) => method.mockReset());
    inertiaPage.url = "/";
    inertiaPage.props = {
        auth: { user: null, permissions: [] },
        flash: {},
        preferences: { locale: "hu", availableLocales: [] },
    };
};
