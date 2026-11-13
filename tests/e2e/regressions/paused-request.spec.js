import { test, expect } from "@playwright/test";
import { withPausedPost } from "../helpers/paused-request.js";

// Synthetic page and intercepted request: no application credentials or data.
test("a closed page cannot leave the paused POST handler pending", async ({
    page,
}) => {
    await page.route("**/__paused_post_probe", (route) =>
        route.fulfill({ contentType: "text/plain", body: "synthetic" }),
    );
    await page.goto("http://127.0.0.1:8001/__paused_post_probe");
    let request;
    await expect(
        withPausedPost(
            page,
            "**/admin/customer-orders",
            async ({ waitForRequest }) => {
                request = page
                    .evaluate(() =>
                        fetch("/admin/customer-orders", {
                            method: "POST",
                        }).catch(() => null),
                    )
                    .catch(() => null);
                await waitForRequest();
                await page.close();
                throw new Error("Synthetic interrupted action");
            },
        ),
    ).rejects.toThrow("Synthetic interrupted action");
    await request;
    expect(page.isClosed()).toBe(true);
});
