import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { resetE2EFixtures } from "../helpers/database.js";
import { selectComboboxOptionMatching } from "../helpers/forms.js";
import { e2eUsers } from "../fixtures/users.js";

test("a goods receipt can be created, opened, posted, and reloaded", async ({
    page,
    browserErrors,
}) => {
    resetE2EFixtures();
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/goods-receipts");

    await expect(
        page.getByRole("heading", { name: "Goods receipts" }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Create receipt" }).click();
    const dialog = page.getByRole("dialog", { name: /Create .*receipt/i });
    await expect(dialog).toBeVisible();

    await selectComboboxOptionMatching(
        page,
        dialog,
        "Purchase order",
        /PO-SUP-2026-000001/,
    );
    await selectComboboxOptionMatching(page, dialog, "Item", /SCR-M4X20/);
    await selectComboboxOptionMatching(page, dialog, "Location", /E2E-LOC/);
    await dialog.getByLabel("Received").fill("4");
    await dialog.getByRole("button", { name: "Create" }).click();

    await expect(dialog).toBeHidden();
    await expect(page).toHaveURL(/\/admin\/goods-receipts\/\d+$/);
    await expect(page.getByText("PO-SUP-2026-000001")).toBeVisible();
    await expect(page.getByText("SCR-M4X20")).toBeVisible();
    await expect(page.getByText("4.000")).toBeVisible();

    await page.getByRole("button", { name: "Post Goods Receipt" }).click();
    const confirm = page.getByRole("alertdialog", {
        name: "Post goods receipt",
    });
    await expect(confirm).toBeVisible();
    await confirm.getByRole("button", { name: "Yes" }).click();
    await expect(page.getByText("Posted")).toBeVisible();
    await page.reload();
    await expect(page.getByText("Posted")).toBeVisible();
    expect(browserErrors).toBeDefined();
});
