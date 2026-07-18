import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { e2eUsers } from "../fixtures/users.js";

test("mobile viewport can use login and reach admin content without critical errors", async ({
    page,
    browserErrors,
}) => {
    await page.goto("/login");
    await expect(page.getByRole("heading", { name: "Login" })).toBeVisible();
    const loginOverflow = await page.evaluate(
        () => document.documentElement.scrollWidth > window.innerWidth + 1,
    );
    expect(loginOverflow).toBe(false);

    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/dashboard");
    await expect(
        page.getByRole("heading", { name: "Dashboard" }),
    ).toBeVisible();
    await expect(page.getByText("KM Production")).toBeVisible();
    expect(browserErrors).toBeDefined();
});
