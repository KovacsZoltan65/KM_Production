import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { resetE2EFixtures } from "../helpers/database.js";
import { e2eUsers } from "../fixtures/users.js";

test("an authorized user can release a stock reservation and the state survives reload", async ({
    page,
    browserErrors,
}) => {
    const { reservationId } = resetE2EFixtures();
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/inventory/stock-reservations");

    const row = page.getByRole("row").filter({ hasText: "E2E-MAT-001" });
    const releaseButton = row.getByRole("button", { name: "Release" });
    await expect(row).toContainText("Active");
    await expect(releaseButton).toBeVisible();

    let continueRequest;
    let requestIntercepted;
    const intercepted = new Promise((resolve) => {
        requestIntercepted = resolve;
    });
    const continueGate = new Promise((resolve) => {
        continueRequest = resolve;
    });

    await page.route(
        `**/admin/inventory/stock-reservations/${reservationId}/release`,
        async (route) => {
            requestIntercepted();
            await continueGate;
            await route.continue();
        },
    );

    await releaseButton.click();
    const dialog = page.getByRole("alertdialog", { name: "Confirm release" });
    await expect(dialog).toBeVisible();
    await dialog.getByRole("button", { name: "Yes" }).click();
    await intercepted;
    await expect(releaseButton).toBeDisabled();

    const releaseResponse = page.waitForResponse(
        (response) =>
            response
                .url()
                .endsWith(
                    `/admin/inventory/stock-reservations/${reservationId}/release`,
                ) && response.request().method() === "PATCH",
    );
    continueRequest();
    expect((await releaseResponse).status()).toBe(303);

    await expect(row).toContainText("Released");
    await expect(row.getByRole("button", { name: "Release" })).toHaveCount(0);
    await page.reload();
    await expect(
        page.getByRole("row").filter({ hasText: "E2E-MAT-001" }),
    ).toContainText("Released");
    expect(browserErrors).toBeDefined();
});

test("a viewer cannot see or invoke the release action", async ({
    page,
    browserErrors,
}) => {
    const { reservationId } = resetE2EFixtures();
    await loginThroughUi(page, e2eUsers.inventoryViewer);
    await page.goto("/admin/inventory/stock-reservations");

    const row = page.getByRole("row").filter({ hasText: "E2E-MAT-001" });
    await expect(row).toContainText("Active");
    await expect(row.getByRole("button", { name: "Release" })).toHaveCount(0);

    browserErrors.allow(/403|Forbidden|Failed to load resource/);
    const status = await page.evaluate(async (reservation) => {
        try {
            await window.axios.patch(
                `/admin/inventory/stock-reservations/${reservation}/release`,
            );
            return 200;
        } catch (error) {
            return error.response?.status;
        }
    }, reservationId);

    expect(status).toBe(403);
    await page.reload();
    await expect(
        page.getByRole("row").filter({ hasText: "E2E-MAT-001" }),
    ).toContainText("Active");
    expect(browserErrors).toBeDefined();
});
