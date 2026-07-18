import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { resetE2EFixtures } from "../helpers/database.js";
import { selectComboboxOptionMatching } from "../helpers/forms.js";
import { e2eUsers } from "../fixtures/users.js";

test("an authorized user can create and reopen a customer order", async ({
    page,
    browserErrors,
}) => {
    resetE2EFixtures();
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
    await dialog.getByLabel("Requested delivery date").fill("2027-03-15");
    await dialog.getByLabel("Notes").fill("E2E customer order UI workflow");
    await dialog.getByRole("button", { name: "Add item" }).click();
    await selectComboboxOptionMatching(page, dialog, "Item", /PRODUCT-AAA/);
    await dialog.getByLabel("Quantity").fill("5");

    let continueRequest;
    let requestIntercepted;
    const intercepted = new Promise((resolve) => {
        requestIntercepted = resolve;
    });
    const continueGate = new Promise((resolve) => {
        continueRequest = resolve;
    });

    await page.route("**/admin/customer-orders", async (route) => {
        if (route.request().method() !== "POST") {
            await route.continue();
            return;
        }

        requestIntercepted();
        await continueGate;
        await route.continue();
    });

    const save = dialog.getByRole("button", { name: "Save" });
    await save.click();
    await intercepted;
    await expect(save).toBeDisabled();
    continueRequest();
    await expect(dialog).toBeHidden();
    await page.unroute("**/admin/customer-orders");

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
    resetE2EFixtures();
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
