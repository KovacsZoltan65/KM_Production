import { defineComponent, h, reactive, watchEffect } from "vue";
import { vi } from "vitest";
import { formatPageTitle } from "@/Utils/pageTitle";

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

const HeadMock = defineComponent({
    name: "Head",
    inheritAttrs: false,
    props: {
        title: { type: String, default: "" },
    },
    setup(props, { attrs, slots }) {
        watchEffect(() => {
            document.title = formatPageTitle(props.title);
        });

        return () => h("div", attrs, slots.default?.());
    },
});

export const inertiaModule = {
    router: inertiaRouter,
    usePage: () => inertiaPage,
    useForm: (values) => createFormMock(values),
    Head: HeadMock,
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
