import { test, expect } from "../helpers/test.js";
import { loginThroughUi, setLocaleThroughUi } from "../helpers/auth.js";
import { selectComboboxOption } from "../helpers/forms.js";
import { e2eUsers } from "../fixtures/users.js";

const toast = (page, message) =>
    page
        .locator('[data-test="global-flash-toast"]')
        .getByText(message, { exact: true });

test("English CRUD feedback covers validation, create, update, delete, and stale flash prevention", async ({
    page,
    browserErrors,
}) => {
    const itemNumber = "E2E-NOTIFY-EN";
    browserErrors.allow(/response: 422 POST .*\/admin\/items$/);

    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/items");

    await page.getByRole("button", { name: "Create item" }).click();
    let dialog = page.getByRole("dialog", { name: "Create item" });
    await expect(dialog).toBeVisible();
    await dialog.getByLabel("Item number").fill(itemNumber);
    await selectComboboxOption(page, dialog, "Unit", "db");
    const validationResponse = page.waitForResponse(
        (response) =>
            response.url().endsWith("/admin/items") &&
            response.request().method() === "POST",
    );
    await dialog.getByRole("button", { name: "Save" }).click();
    await validationResponse;

    await expect(dialog).toBeVisible();
    await expect(dialog.getByText(/required/i).first()).toBeVisible();
    const validationToast = toast(
        page,
        "Check the entered data and correct the highlighted errors.",
    );
    await expect(validationToast).toBeVisible();
    await expect(validationToast).toHaveCount(1);

    await dialog.getByLabel("Name").fill("Notification item");
    await dialog.getByRole("button", { name: "Save" }).click();

    await expect(dialog).toBeHidden();
    await expect(toast(page, "Created successfully.")).toBeVisible();

    await page.getByRole("textbox", { name: "Search" }).fill(itemNumber);
    const itemSearchResponse = page.waitForResponse(
        (response) =>
            response.url().includes("/admin/items?") &&
            response.request().method() === "GET",
    );
    await page.getByRole("button", { name: "Search" }).click();
    await itemSearchResponse;

    let row = page.getByRole("row").filter({ hasText: itemNumber });
    await expect(row).toContainText("Notification item");
    await row.getByRole("button", { name: "Edit" }).click();
    dialog = page.getByRole("dialog", { name: /Edit Items/i });
    await expect(dialog).toBeVisible();
    await dialog.getByLabel("Name").fill("Updated notification item");
    await dialog.getByRole("button", { name: "Save" }).click();

    await expect(dialog).toBeHidden();
    await expect(toast(page, "Updated successfully.")).toBeVisible();
    row = page.getByRole("row").filter({ hasText: itemNumber });
    await expect(row).toContainText("Updated notification item");

    await row.getByRole("button", { name: "Delete" }).click();
    const confirmation = page.getByRole("alertdialog", {
        name: "Confirm delete",
    });
    await expect(confirmation).toBeVisible();
    await confirmation.getByRole("button", { name: "Yes" }).click();

    await expect(toast(page, "Deleted successfully.")).toBeVisible();
    await expect(
        page.getByRole("row").filter({ hasText: itemNumber }),
    ).toHaveCount(0);

    await page.reload();
    await expect(toast(page, "Deleted successfully.")).toHaveCount(0);
    await expect(
        page.getByRole("heading", { name: "Items", exact: true }),
    ).toBeVisible();
});

test("a protected self-delete shows one safe error and leaves the page usable", async ({
    page,
    browserErrors,
}) => {
    browserErrors.allow(/response: 422 DELETE .*\/admin\/users\/\d+$/);

    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/users");
    await page
        .getByRole("textbox", { name: "Search" })
        .fill(e2eUsers.admin.email);
    const userSearchResponse = page.waitForResponse(
        (response) =>
            response.url().includes("/admin/users?") &&
            response.request().method() === "GET",
    );
    await page.getByRole("button", { name: "Search" }).click();
    await userSearchResponse;

    const row = page.getByRole("row").filter({ hasText: e2eUsers.admin.email });
    await expect(row).toBeVisible();
    await row.getByRole("button", { name: "Delete" }).click();
    const confirmation = page.getByRole("alertdialog", {
        name: "Confirm delete",
    });
    await expect(confirmation).toBeVisible();
    await confirmation.getByRole("button", { name: "Yes" }).click();

    const errorToast = toast(
        page,
        "Check the entered data and correct the highlighted errors.",
    );
    await expect(errorToast).toBeVisible();
    await expect(errorToast).toHaveCount(1);
    await expect(row).toBeVisible();
    await expect(page.locator("body")).not.toContainText(
        /SQLSTATE|stack trace|ValidationException|Illuminate\\/,
    );
    await expect(
        page.getByRole("button", { name: "Create user" }),
    ).toBeEnabled();
});

test("Hungarian success feedback can switch back to an isolated English locale", async ({
    page,
}) => {
    const itemNumber = "E2E-NOTIFY-HU";

    await loginThroughUi(page, e2eUsers.admin);
    await setLocaleThroughUi(page, "hu");
    await page.goto("/admin/items");

    await page.getByRole("button", { name: "Cikk létrehozása" }).click();
    const dialog = page.getByRole("dialog", {
        name: "Cikk létrehozása",
    });
    await expect(dialog).toBeVisible();
    await dialog.getByLabel("Cikkszám").fill(itemNumber);
    await dialog.getByLabel("Név").fill("Magyar visszajelzés teszt");
    await selectComboboxOption(page, dialog, "Egység", "db");
    await expect(dialog.getByRole("button", { name: "Mentés" })).toBeEnabled();
    await dialog.getByRole("button", { name: "Mentés" }).click();

    await expect(dialog).toBeHidden();
    await expect(toast(page, "Sikeresen létrehozva.")).toBeVisible();
    await page.getByRole("textbox", { name: "Keresés" }).fill(itemNumber);
    const hungarianSearchResponse = page.waitForResponse(
        (response) =>
            response.url().includes("/admin/items?") &&
            response.request().method() === "GET",
    );
    await page.getByRole("button", { name: "Keresés" }).click();
    await hungarianSearchResponse;
    await expect(
        page.getByRole("row").filter({ hasText: itemNumber }),
    ).toContainText("Magyar visszajelzés teszt");

    await setLocaleThroughUi(page, "en");
    await expect(
        toast(page, "The display language was changed successfully."),
    ).toBeVisible();
    await expect(
        page.getByRole("heading", { name: "Items", exact: true }),
    ).toBeVisible();
});
