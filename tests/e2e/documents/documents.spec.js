import { resolve } from "node:path";
import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { resetE2EFixtures } from "../helpers/database.js";
import { e2eUsers } from "../fixtures/users.js";

const fixtureFile = (name) =>
    resolve(import.meta.dirname, "../fixtures/files", name);

async function openUploadDialog(page) {
    await page.getByRole("button", { name: "Upload Document" }).click();
    return page.getByRole("dialog", { name: "Upload Document" });
}

async function fillUploadForm(page, dialog, { itemId, title, file }) {
    await dialog.getByRole("combobox", { name: "Type" }).click();
    await page.getByRole("option", { name: "Work note" }).click();
    await dialog.getByRole("combobox", { name: "Linked entity" }).click();
    await page.getByRole("option", { name: "Item", exact: true }).click();
    await dialog.getByLabel("Entity ID").fill(String(itemId));
    await dialog.getByLabel("Title").fill(title);
    await dialog.getByLabel("File").setInputFiles(file);
}

test("document upload creates deterministic versions and a previous version can become current", async ({
    page,
    browserErrors,
}) => {
    const { itemId } = resetE2EFixtures();
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto("/admin/documents");

    const firstDialog = await openUploadDialog(page);
    await fillUploadForm(page, firstDialog, {
        itemId,
        title: "E2E Document Workflow",
        file: fixtureFile("sample-document.txt"),
    });

    let continueUpload;
    let uploadIntercepted;
    const intercepted = new Promise((resolve) => {
        uploadIntercepted = resolve;
    });
    const continueGate = new Promise((resolve) => {
        continueUpload = resolve;
    });
    await page.route("**/admin/documents", async (route) => {
        if (route.request().method() !== "POST") {
            await route.continue();
            return;
        }

        uploadIntercepted();
        await continueGate;
        await route.continue();
    });

    const firstUploadButton = firstDialog.getByRole("button", {
        name: "Upload",
    });
    await firstUploadButton.click();
    await intercepted;
    await expect(firstUploadButton).toBeDisabled();
    continueUpload();
    await expect(page).toHaveURL(/\/admin\/documents\/\d+$/);
    await expect(
        page.getByRole("heading", {
            level: 1,
            name: "E2E Document Workflow",
        }),
    ).toBeVisible();
    await expect(page.getByTestId("document-version-1")).toContainText(
        "sample-document.txt",
    );
    await page.unroute("**/admin/documents");

    await page.goto("/admin/documents");
    const secondDialog = await openUploadDialog(page);
    await fillUploadForm(page, secondDialog, {
        itemId,
        title: "E2E Document Workflow v2",
        file: fixtureFile("sample-document-v2.txt"),
    });
    await secondDialog.getByRole("button", { name: "Upload" }).click();

    await expect(page).toHaveURL(/\/admin\/documents\/\d+$/);
    await expect(
        page.getByRole("heading", {
            level: 1,
            name: "E2E Document Workflow v2",
        }),
    ).toBeVisible();
    await expect(page.getByTestId("document-version-2")).toContainText(
        "sample-document-v2.txt",
    );

    const firstVersion = page.getByTestId("document-version-1");
    await firstVersion.getByRole("button", { name: "Make current" }).click();
    await expect(firstVersion).toContainText("Current");
    await page.reload();
    await expect(page.getByTestId("document-version-1")).toContainText(
        "Current",
    );
    expect(browserErrors).toBeDefined();
});
