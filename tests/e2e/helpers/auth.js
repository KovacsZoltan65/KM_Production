import { expect } from "@playwright/test";

export async function loginThroughUi(page, user) {
    await page.goto("/login");
    await page.getByLabel("Email").fill(user.email);
    await page.getByLabel("Password").fill(user.password);
    await page.getByRole("button", { name: "Login" }).click();

    await expect(page).toHaveURL(/\/dashboard$/, { timeout: 30_000 });
    await expect(
        page.getByRole("heading", { name: "Dashboard" }),
    ).toBeVisible();
    await expect(page.getByText(user.name, { exact: true })).toBeAttached();
    await expect(page.locator("#nprogress")).toHaveCount(0);
}
