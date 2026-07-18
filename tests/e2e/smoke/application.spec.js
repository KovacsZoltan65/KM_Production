import { test, expect } from "../helpers/test.js";

test("the login page and its production assets load without critical browser errors", async ({
    page,
    browserErrors,
}) => {
    const response = await page.goto("/login");

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(/Login.*KM Production|KM Production.*Login/);
    await expect(page.getByRole("heading", { name: "Login" })).toBeVisible();
    await expect(page.getByLabel("Email")).toBeVisible();
    await expect(page.getByLabel("Password")).toBeVisible();
    expect(browserErrors).toBeDefined();
});
