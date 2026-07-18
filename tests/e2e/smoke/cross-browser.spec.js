import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { e2eUsers } from "../fixtures/users.js";

test("browser smoke covers login, Inertia navigation, and logout", async ({
    page,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.admin);
    await page.locator('a[href="/admin/customer-orders"]').click();
    await expect(page).toHaveURL(/\/admin\/customer-orders$/);
    await expect(
        page.getByRole("heading", { name: "Customer orders" }),
    ).toBeVisible();

    await page.getByRole("button", { name: "Open user menu" }).click();
    await page.getByText("Logout", { exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
    expect(browserErrors).toBeDefined();
});
