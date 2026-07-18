import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { resetE2EFixtures } from "../helpers/database.js";
import { selectComboboxOptionMatching } from "../helpers/forms.js";
import { e2eUsers } from "../fixtures/users.js";

test("a production plan can be created from a seeded customer order", async ({
    page,
    browserErrors,
}) => {
    resetE2EFixtures();
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/production-plans");

    await expect(
        page.getByRole("heading", { name: "Production plans" }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Create plan" }).click();
    const dialog = page.getByRole("dialog", { name: "Create production plan" });
    await expect(dialog).toBeVisible();

    await selectComboboxOptionMatching(
        page,
        dialog,
        "Customer order",
        /E2E-SO-0001/,
    );
    await dialog.getByLabel("Planned start").fill("2027-04-01");
    await dialog.getByLabel("Planned finish").fill("2027-04-10");
    await dialog.getByLabel("Notes").fill("E2E production plan UI workflow");
    await dialog.getByRole("button", { name: "Save" }).click();

    await expect(dialog).toBeHidden();
    const row = page.getByRole("row").filter({ hasText: "E2E-SO-0001" });
    await expect(row).toContainText("Draft");
    await row.getByRole("link").click();
    await expect(page).toHaveURL(/\/admin\/production-plans\/\d+$/);
    await expect(page.getByText("E2E-SO-0001", { exact: true })).toBeVisible();
    await expect(page.getByText("Draft").first()).toBeVisible();

    await page.reload();
    await expect(page.getByText("E2E-SO-0001", { exact: true })).toBeVisible();
    expect(browserErrors).toBeDefined();
});
