import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { expectNoSeriousAccessibilityViolations } from "../helpers/a11y.js";
import { e2eUsers } from "../fixtures/users.js";

test("critical pages have no serious or critical axe violations", async ({
    page,
    browserErrors,
    e2eData,
}) => {
    const { productionTaskId } = e2eData;

    await page.goto("/login");
    await expectNoSeriousAccessibilityViolations(page, "login");

    await loginThroughUi(page, e2eUsers.admin);
    await expectNoSeriousAccessibilityViolations(page, "dashboard");

    await page.goto("/admin/customer-orders");
    await expectNoSeriousAccessibilityViolations(page, "customer orders table");
    await page.getByRole("button", { name: "Create order" }).click();
    const customerDialog = page.getByRole("dialog", {
        name: "Create customer order",
    });
    await expect(customerDialog).toBeVisible();
    // Scan the rendered modal, rather than a partially transparent enter frame.
    await expect(customerDialog).toHaveCSS("opacity", "1");
    await expectNoSeriousAccessibilityViolations(page, "customer order modal");
    await page.keyboard.press("Escape");

    await page.goto("/admin/documents");
    await page.getByRole("button", { name: "Upload Document" }).click();
    const documentDialog = page.getByRole("dialog", {
        name: "Upload Document",
        exact: true,
    });
    await expect(documentDialog).toBeVisible();
    await expect(documentDialog).toHaveCSS("opacity", "1");
    await expectNoSeriousAccessibilityViolations(page, "document upload modal");
    await page.keyboard.press("Escape");

    await page.goto("/admin/inventory/stock-reservations");
    await expectNoSeriousAccessibilityViolations(page, "stock reservations");

    await page.goto(`/admin/production-tasks/${productionTaskId}`);
    await expectNoSeriousAccessibilityViolations(
        page,
        "production task detail",
    );
    browserErrors.allow(/AbortError|The play\(\) request was interrupted/);
});
