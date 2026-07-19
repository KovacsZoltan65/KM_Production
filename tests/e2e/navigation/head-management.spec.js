import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { e2eUsers } from "../fixtures/users.js";

test("Inertia navigation updates and restores the localized document title", async ({
    page,
}) => {
    await page.goto("/login");
    await expect(page).toHaveTitle("Login | KM Production");

    await loginThroughUi(page, e2eUsers.inventoryViewer);
    await expect(page).toHaveTitle("Dashboard | KM Production");

    await page.getByText("Inventory", { exact: true }).click();
    await expect(page).toHaveURL(/\/admin\/inventory$/);
    await expect(page).toHaveTitle("Inventory Dashboard | KM Production");

    await page.goBack();
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page).toHaveTitle("Dashboard | KM Production");

    await page.goForward();
    await expect(page).toHaveURL(/\/admin\/inventory$/);
    await expect(page).toHaveTitle("Inventory Dashboard | KM Production");
});
