import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { e2eUsers } from "../fixtures/users.js";

test("restricted navigation matches backend authorization", async ({
    page,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.inventoryViewer);

    const inventoryLink = page.getByText("Inventory", { exact: true });
    await expect(inventoryLink).toBeVisible();
    await expect(
        page.getByRole("link", { name: "Document Library", exact: true }),
    ).toHaveCount(0);

    await inventoryLink.click();
    await expect(page).toHaveURL(/\/admin\/inventory$/);
    await expect(
        page.getByRole("heading", { name: "Inventory" }),
    ).toBeVisible();

    browserErrors.allow(/403|Forbidden|Failed to load resource/);
    const forbiddenResponse = await page.goto("/admin/documents");
    expect(forbiddenResponse?.status()).toBe(403);
});
