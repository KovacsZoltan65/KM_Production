import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { e2eUsers } from "../fixtures/users.js";

test("login form supports tab order and enter submission", async ({
    page,
    browserErrors,
}) => {
    await page.goto("/login");
    await expect(page.getByLabel("Email")).toBeFocused();
    await page.keyboard.type(e2eUsers.admin.email);
    await page.keyboard.press("Tab");
    await expect(page.getByLabel("Password")).toBeFocused();
    await page.keyboard.type("incorrect-password");
    await page.keyboard.press("Enter");

    await expect(
        page.getByText("These credentials do not match our records."),
    ).toBeVisible();
    await expect(page.getByLabel("Email")).toBeVisible();
    await page.getByLabel("Password").fill(e2eUsers.admin.password);
    await page.keyboard.press("Enter");
    await expect(page).toHaveURL(/\/dashboard$/);
    expect(browserErrors).toBeDefined();
});

test("admin navigation and modal interactions are keyboard reachable", async ({
    page,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/customer-orders");

    const navLink = page.locator('a[href="/admin/customer-orders"]');
    await navLink.focus();
    await expect(navLink).toBeFocused();
    await expect(navLink).toHaveAttribute("aria-current", "page");

    const createButton = page.getByRole("button", { name: "Create order" });
    await createButton.focus();
    await page.keyboard.press("Enter");
    const dialog = page.getByRole("dialog", { name: "Create customer order" });
    await expect(dialog).toBeVisible();
    const customerCombobox = dialog.getByRole("combobox", {
        name: "Customer",
    });
    await expect(customerCombobox).toBeVisible();
    // PrimeVue moves focus after the dialog's enter transition completes.
    const closeButton = dialog.getByRole("button", {
        name: "Close",
        exact: true,
    });
    await expect(closeButton).toBeFocused();
    await page.keyboard.press("Tab");
    await expect(customerCombobox).toBeFocused();
    await page.keyboard.press("Shift+Tab");
    await expect(closeButton).toBeFocused();
    await page.keyboard.press("Shift+Tab");
    await expect(
        dialog.getByRole("button", { name: "Save", exact: true }),
    ).toBeFocused();
    await page.keyboard.press("Tab");
    await expect(closeButton).toBeFocused();
    await page.keyboard.press("Tab");
    await expect(customerCombobox).toBeFocused();
    const cancelButton = dialog.getByRole("button", { name: "Cancel" });

    for (let attempt = 0; attempt < 16; attempt += 1) {
        if (
            await cancelButton.evaluate(
                (element) => element === document.activeElement,
            )
        ) {
            break;
        }

        await page.keyboard.press("Tab");
        expect(
            await dialog.evaluate((element) =>
                element.contains(document.activeElement),
            ),
        ).toBe(true);
    }

    await expect(cancelButton).toBeFocused();
    await page.keyboard.press("Enter");
    await expect(dialog).toBeHidden();
    await expect(createButton).toBeFocused();
    expect(browserErrors).toBeDefined();
});

test("confirmation dialogs are reachable and cancelable from the keyboard", async ({
    page,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/inventory/stock-reservations");

    const row = page.getByRole("row").filter({
        has: page.getByRole("cell", {
            name: /^E2E-STOCK-RESERVATION-PARTIAL-REFRESH - /,
        }),
    });
    await expect(row).toHaveCount(1);
    await expect(
        row.getByRole("cell", { name: "E2E-LOC", exact: true }),
    ).toBeVisible();
    await row.getByRole("button", { name: "Release" }).focus();
    await page.keyboard.press("Enter");
    const dialog = page.getByRole("alertdialog", { name: "Confirm release" });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole("button", { name: "No" })).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(dialog).toBeHidden();
    await expect(row).toContainText("Active");
    expect(browserErrors).toBeDefined();
});
