import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { selectComboboxOptionMatching } from "../helpers/forms.js";
import { e2eUsers } from "../fixtures/users.js";

test("a production plan can be created from a seeded customer order", async ({
    page,
    browserErrors,
}) => {
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
    const visibleDatePanels = page.locator(".p-datepicker-panel:visible");
    const plannedStart = dialog.getByLabel("Planned start");
    await plannedStart.fill("2027-04-01");
    await expect(visibleDatePanels).toHaveCount(1);
    await plannedStart.press("Escape");
    await expect(visibleDatePanels).toHaveCount(0);
    const plannedFinish = dialog.getByLabel("Planned finish");
    await plannedFinish.fill("2027-04-10");
    await expect(visibleDatePanels).toHaveCount(1);
    await plannedFinish.press("Escape");
    await expect(visibleDatePanels).toHaveCount(0);
    await dialog.getByLabel("Notes").fill("E2E production plan UI workflow");
    const createResponse = page.waitForResponse(
        (response) =>
            response.request().method() === "POST" &&
            new URL(response.url()).pathname === "/admin/production-plans",
    );
    await dialog.getByRole("button", { name: "Save" }).click();
    await expect((await createResponse).status()).toBe(302);

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
