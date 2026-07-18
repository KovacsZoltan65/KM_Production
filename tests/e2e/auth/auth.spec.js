import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { e2eUsers } from "../fixtures/users.js";

test("an E2E admin can sign in through the real login form", async ({
    page,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.admin);
    await expect(
        page.getByRole("button", { name: "Open user menu" }),
    ).toBeVisible();
    expect(browserErrors).toBeDefined();
});

test("invalid credentials stay on login and never expose the password", async ({
    page,
    browserErrors,
}) => {
    await page.goto("/login");
    await page.getByLabel("Email").fill(e2eUsers.admin.email);
    await page.getByLabel("Password").fill("incorrect-password");
    await page.getByRole("button", { name: "Login" }).click();

    await expect(page).toHaveURL(/\/login$/);
    await expect(
        page.getByText("These credentials do not match our records."),
    ).toBeVisible();
    await expect(page.getByLabel("Password")).toHaveValue("");
    await expect(
        page.getByRole("button", { name: "Open user menu" }),
    ).toHaveCount(0);
    expect(page.url()).not.toContain("incorrect-password");
    expect(browserErrors).toBeDefined();
});

test("logout ends the session and protected pages redirect to login", async ({
    page,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.admin);
    await page.getByRole("button", { name: "Open user menu" }).click();
    await page.getByText("Logout", { exact: true }).click();

    await expect(page).toHaveURL(/\/login$/);
    await page.goto("/admin/documents");
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole("heading", { name: "Login" })).toBeVisible();
    expect(browserErrors).toBeDefined();
});
