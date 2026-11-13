import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import {
    fillDatePicker,
    selectComboboxOptionMatching,
} from "../helpers/forms.js";
import { withPausedPost } from "../helpers/paused-request.js";
import { e2eUsers } from "../fixtures/users.js";

test("an authorized user can create and reopen a customer order", async ({
    page,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/customer-orders");

    await expect(
        page.getByRole("heading", { name: "Customer orders" }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Create order" }).click();
    const dialog = page.getByRole("dialog", { name: "Create customer order" });
    await expect(dialog).toBeVisible();

    await selectComboboxOptionMatching(
        page,
        dialog,
        "Customer",
        /E2E Customer/,
    );
    await fillDatePicker(page, dialog, "Requested delivery date", "2027-03-15");
    await dialog.getByLabel("Notes").fill("E2E customer order UI workflow");
    await dialog.getByRole("button", { name: "Add item" }).click();
    await selectComboboxOptionMatching(page, dialog, "Item", /PRODUCT-AAA/);
    await dialog.getByLabel("Quantity").fill("5");

    const save = dialog.getByRole("button", { name: "Save" });
    await withPausedPost(
        page,
        "**/admin/customer-orders",
        async ({ waitForRequest, release }) => {
            await save.click();
            await waitForRequest();
            await expect(save).toBeDisabled();
            release();
            await expect(dialog).toBeHidden({ timeout: 15_000 });
        },
    );
    await expect(
        page.locator(".p-toast-message-success").filter({
            hasText: "Customer order created.",
        }),
    ).toBeVisible();

    const row = page
        .getByRole("row")
        .filter({ hasText: "E2E Customer" })
        .filter({ hasText: "2027-03-15" });
    await expect(row).toContainText("Draft");
    await row.getByRole("link").click();
    await expect(page).toHaveURL(/\/admin\/customer-orders\/\d+$/);
    await expect(page.getByText("E2E Customer", { exact: true })).toBeVisible();
    await expect(page.getByText("PRODUCT-AAA")).toBeVisible();

    await page.reload();
    await expect(page.getByText("E2E Customer", { exact: true })).toBeVisible();
    await expect(page.getByText("PRODUCT-AAA")).toBeVisible();
    expect(browserErrors).toBeDefined();
});

test("a user without create permission cannot create customer orders", async ({
    page,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.inventoryViewer);
    await page.goto("/admin/customer-orders");

    browserErrors.allow(/403|Forbidden|Failed to load resource/);
    await expect(page).toHaveURL(/\/admin\/customer-orders$/);
    await expect(
        page.getByRole("button", { name: "Create order" }),
    ).toHaveCount(0);

    const status = await page.evaluate(async () => {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        const response = await fetch("/admin/customer-orders", {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token || "",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
                customer_id: 1,
                items: [{ item_id: 1, quantity: 1, unit: "db" }],
            }),
        });

        return response.status;
    });

    expect(status).toBe(403);
    expect(browserErrors).toBeDefined();
});
