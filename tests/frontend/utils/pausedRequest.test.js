import { EventEmitter } from "node:events";
import { afterEach, describe, expect, it, vi } from "vitest";
import { withPausedPost } from "../../e2e/helpers/paused-request.js";

afterEach(() => vi.useRealTimers());
function fakePage() {
    const page = new EventEmitter();
    let handler;
    let closed = false;
    page.isClosed = () => closed;
    page.route = vi.fn(async (_, callback) => {
        handler = callback;
    });
    page.unroute = vi.fn(async () => {
        if (closed) throw new Error("Target page has been closed");
    });
    page.close = () => {
        closed = true;
        page.emit("close");
    };
    const route = {
        request: () => ({ method: () => "POST" }),
        continue: vi.fn(async () => {}),
        abort: vi.fn(async () => {}),
    };
    return { page, route, dispatch: () => handler(route) };
}

describe("paused POST lifecycle", () => {
    it("keeps a request held until its pending UI can be checked", async () => {
        const { page, route, dispatch } = fakePage();
        await withPausedPost(
            page,
            "pattern",
            async ({ waitForRequest, release }) => {
                const pending = dispatch();
                await waitForRequest();
                expect(route.continue).not.toHaveBeenCalled();
                release();
                await pending;
            },
        );
        expect(route.continue).toHaveBeenCalledOnce();
        expect(page.unroute).toHaveBeenCalledWith(
            "pattern",
            expect.any(Function),
        );
        expect(page.listenerCount("close")).toBe(0);
    });

    it("bounds a missing request and removes its handler and timer", async () => {
        vi.useFakeTimers();
        const { page } = fakePage();
        const result = withPausedPost(
            page,
            "pattern",
            async ({ waitForRequest }) => waitForRequest(),
            { timeout: 50 },
        );
        const assertion = expect(result).rejects.toThrow(
            "Paused POST timed-out.",
        );
        await vi.advanceTimersByTimeAsync(50);
        await assertion;
        expect(page.unroute).toHaveBeenCalledOnce();
        expect(page.listenerCount("close")).toBe(0);
        expect(vi.getTimerCount()).toBe(0);
    });

    it("aborts and drains an intercepted handler after close and a failing action", async () => {
        vi.useFakeTimers();
        const { page, route, dispatch } = fakePage();
        let pending;
        await expect(
            withPausedPost(page, "pattern", async ({ waitForRequest }) => {
                pending = dispatch();
                await waitForRequest();
                page.close();
                throw new Error("Synthetic interrupted action");
            }),
        ).rejects.toThrow("Synthetic interrupted action");
        await pending;
        expect(route.abort).toHaveBeenCalledExactlyOnceWith("aborted");
        expect(route.continue).not.toHaveBeenCalled();
        expect(page.unroute).toHaveBeenCalledOnce();
        expect(page.listenerCount("close")).toBe(0);
        expect(vi.getTimerCount()).toBe(0);
    });

    it("cleans up an assertion failure before a request arrives", async () => {
        const { page } = fakePage();
        await expect(
            withPausedPost(page, "pattern", async () => {
                throw new Error("Synthetic assertion");
            }),
        ).rejects.toThrow("Synthetic assertion");
        expect(page.unroute).toHaveBeenCalledOnce();
        expect(page.listenerCount("close")).toBe(0);
    });
});
