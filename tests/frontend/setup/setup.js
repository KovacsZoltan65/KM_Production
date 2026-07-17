import { config } from "@vue/test-utils";
import { afterEach, beforeEach, vi } from "vitest";

vi.mock("@inertiajs/vue3", async () => {
    const { inertiaModule } = await import("../mocks/inertia.js");
    return inertiaModule;
});

vi.mock("laravel-vue-i18n", () => ({
    trans: (key, replacements = {}) =>
        Object.entries(replacements).reduce(
            (text, [name, value]) => text.replace(`:${name}`, String(value)),
            key,
        ),
    loadLanguageAsync: vi.fn().mockResolvedValue(undefined),
}));

config.global.mocks = {
    $t: (key) => key,
};

class ResizeObserverMock {
    observe() {}
    unobserve() {}
    disconnect() {}
}

class IntersectionObserverMock extends ResizeObserverMock {}

Object.defineProperty(window, "matchMedia", {
    writable: true,
    value: vi.fn().mockImplementation((query) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});

globalThis.ResizeObserver = ResizeObserverMock;
globalThis.IntersectionObserver = IntersectionObserverMock;

beforeEach(async () => {
    const { resetInertiaMock } = await import("../mocks/inertia.js");
    resetInertiaMock();
    document.documentElement.lang = "hu";
    window.localStorage?.clear();
    window.sessionStorage?.clear();
});

afterEach(() => {
    vi.useRealTimers();
});
