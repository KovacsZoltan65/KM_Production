import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { resetE2EFixtures } from "../helpers/database.js";
import { selectComboboxOption } from "../helpers/forms.js";
import { e2eUsers } from "../fixtures/users.js";

test("common CRUD shows validation and success feedback for create, update, and delete", async ({
    page,
    browserErrors,
}) => {
    resetE2EFixtures();
    const itemNumber = `E2E-NOTIFY-${Date.now()}`;
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/items");

    await page.getByRole("button", { name: "Create item" }).click();
    let dialog = page.getByRole("dialog", { name: "Create item" });
    await dialog.getByLabel("Item number").fill(itemNumber);
    await selectComboboxOption(page, dialog, "Unit", "db");
    await dialog.getByRole("button", { name: "Save" }).click();

    await expect(dialog).toBeVisible();
    await expect(dialog.getByText(/required/i).first()).toBeVisible();
    await expect(
        page.locator(".p-toast-message-error").filter({
            hasText: "Check the entered data",
        }),
    ).toBeVisible();

    await dialog.getByLabel("Name").fill("Notification item");
    await dialog.getByRole("button", { name: "Save" }).click();

    await expect(dialog).toBeHidden();
    await expect(
        page.locator(".p-toast-message-success").filter({
            hasText: "Created successfully.",
        }),
    ).toBeVisible();

    await page.getByRole("textbox", { name: "Search" }).fill(itemNumber);
    await page.getByRole("button", { name: "Search" }).click();

    let row = page.getByRole("row").filter({ hasText: itemNumber });
    await expect(row).toContainText("Notification item");
    await row.getByRole("button", { name: "Edit" }).click();
    dialog = page.getByRole("dialog", { name: /Edit Items/i });
    await dialog.getByLabel("Name").fill("Updated notification item");
    await dialog.getByRole("button", { name: "Save" }).click();

    await expect(
        page.locator(".p-toast-message-success").filter({
            hasText: "Updated successfully.",
        }),
    ).toBeVisible();
    row = page.getByRole("row").filter({ hasText: itemNumber });
    await expect(row).toContainText("Updated notification item");

    await row.getByRole("button", { name: "Delete" }).click();
    const confirmation = page.getByRole("alertdialog", {
        name: "Confirm delete",
    });
    await confirmation.getByRole("button", { name: "Yes" }).click();

    await expect(
        page.locator(".p-toast-message-success").filter({
            hasText: "Deleted successfully.",
        }),
    ).toBeVisible();
    await expect(
        page.getByRole("row").filter({ hasText: itemNumber }),
    ).toHaveCount(0);
    expect(browserErrors).toBeDefined();
});
