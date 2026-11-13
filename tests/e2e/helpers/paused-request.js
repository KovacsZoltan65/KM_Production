/** Hold a POST only while its pending UI state is checked; always drain our handler. */
export async function withPausedPost(
    page,
    pattern,
    action,
    { timeout = 7_500 } = {},
) {
    let releaseGate;
    let resolveInterception;
    let released = false;
    let cancelled = false;
    const pending = new Set();
    const gate = new Promise((resolve) => {
        releaseGate = resolve;
    });
    const intercepted = new Promise((resolve) => {
        resolveInterception = resolve;
    });
    const release = () => {
        released = true;
        releaseGate();
    };
    const cancel = (reason) => {
        cancelled = true;
        resolveInterception(reason);
        releaseGate();
    };
    const onClose = () => cancel("closed");
    const timer = setTimeout(() => cancel("timed-out"), timeout);
    const handler = (route) => {
        const work = (async () => {
            try {
                if (route.request().method() !== "POST") {
                    await route.continue();
                    return;
                }
                resolveInterception("intercepted");
                await gate;
                if (cancelled) await route.abort("aborted");
                else await route.continue();
            } catch (error) {
                if (!page.isClosed()) throw error;
            }
        })();
        pending.add(work);
        return work.finally(() => pending.delete(work));
    };
    page.on("close", onClose);
    try {
        await page.route(pattern, handler);
        return await action({
            release,
            waitForRequest: async () => {
                const status = await intercepted;
                if (status !== "intercepted")
                    throw new Error(`Paused POST ${status}.`);
            },
        });
    } finally {
        clearTimeout(timer);
        page.off("close", onClose);
        if (!released) cancel("cancelled");
        try {
            await page.unroute(pattern, handler);
        } catch (error) {
            // A closed page has already disposed its routes; preserve the action error.
            if (!page.isClosed()) throw error;
        } finally {
            await Promise.all(pending);
        }
    }
}
