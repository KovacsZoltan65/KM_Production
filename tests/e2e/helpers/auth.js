import { expect } from "@playwright/test";

export async function loginThroughUi(page, user) {
    await page.goto("/login");
    await expect(page.locator("html")).toHaveAttribute("lang", "en");
    await page.getByLabel("Email").fill(user.email);
    await page.getByLabel("Password").fill(user.password);
    await page.getByRole("button", { name: "Login" }).click();

    await expect(page).toHaveURL(/\/dashboard$/, { timeout: 30_000 });
    await expect(
        page.getByRole("heading", { name: "Dashboard" }),
    ).toBeVisible();
    await expect(page.getByText(user.name, { exact: true })).toBeAttached();
    await expect(page.locator("html")).toHaveAttribute("lang", "en");
    await expect(page.locator("#nprogress")).toHaveCount(0);
}

export async function setLocaleThroughUi(page, locale) {
    const localeNames =
        locale === "hu" ? /^(Hungarian|Magyar)$/ : /^(English|Angol)$/;
    const responsePromise = page.waitForResponse(
        (response) =>
            response.url().endsWith("/preferences/locale") &&
            response.request().method() === "POST",
    );

    await page.getByRole("combobox", { name: /^(Language|Nyelv)$/ }).click();
    await page.getByRole("option", { name: localeNames }).click();
    await responsePromise;
    await expect(page.locator("html")).toHaveAttribute("lang", locale);
    await expect(page.locator("#nprogress")).toHaveCount(0);
}
