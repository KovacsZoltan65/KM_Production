import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { resetE2EFixtures } from "../helpers/database.js";
import { selectComboboxOptionMatching } from "../helpers/forms.js";
import { e2eUsers } from "../fixtures/users.js";

test("a production task can be started, finished, and accepted by quality check", async ({
    page,
    browserErrors,
}) => {
    const { productionTaskId } = resetE2EFixtures();
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/production-tasks");

    const taskRow = page.getByRole("row").filter({ hasText: "AAA/2026/0001" });
    await expect(taskRow).toContainText("Ready");
    await taskRow.getByRole("link", { name: "AAA/2026/0001" }).click();

    await expect(page).toHaveURL(
        new RegExp(`/admin/production-tasks/${productionTaskId}$`),
    );
    await expect(page.getByText("Ready")).toBeVisible();
    await page.getByRole("button", { name: "Start" }).click();
    await expect(page.getByText("In progress")).toBeVisible();
    await page.reload();
    await expect(page.getByText("In progress")).toBeVisible();

    await page.getByRole("button", { name: "Finish" }).click();
    await expect(page.getByText("Waiting for check")).toBeVisible();
    await expect(page.getByRole("button", { name: "Start" })).toHaveCount(0);
    await expect(page.getByRole("button", { name: "Finish" })).toHaveCount(0);

    const qualityForm = page
        .locator("form")
        .filter({ hasText: "Record check" });
    await selectComboboxOptionMatching(
        page,
        qualityForm,
        "Inspector",
        /Minta Hegesztő/,
    );
    await selectComboboxOptionMatching(page, qualityForm, "Result", /Accepted/);
    await qualityForm.getByLabel("Notes").fill("E2E accepted quality check");
    await qualityForm.getByRole("button", { name: "Record check" }).click();

    await expect(page.getByText("Completed")).toBeVisible();
    await expect(page.getByText("E2E accepted quality check")).toBeVisible();
    await page.reload();
    await expect(page.getByText("Completed")).toBeVisible();
    await expect(page.getByText("E2E accepted quality check")).toBeVisible();
    expect(browserErrors).toBeDefined();
});
