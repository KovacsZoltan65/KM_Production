import { spawnSync } from "node:child_process";
import { resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
import { selectComboboxOptionMatching } from "../helpers/forms.js";
import { e2eUsers } from "../fixtures/users.js";

const projectRoot = resolve(
    fileURLToPath(new URL("../../..", import.meta.url)),
);
const databasePath = resolve(projectRoot, "database/e2e.sqlite");

function scalarSql(sql, parameters) {
    const result = spawnSync(
        "php",
        [
            "-r",
            '$database = new PDO("sqlite:" . $argv[1]);' +
                "$statement = $database->prepare($argv[2]);" +
                "$parameters = json_decode($argv[3], true, 512, JSON_THROW_ON_ERROR);" +
                "$statement->execute($parameters);" +
                "echo json_encode($statement->fetchColumn(), JSON_THROW_ON_ERROR);",
            databasePath,
            sql,
            JSON.stringify(parameters),
        ],
        {
            cwd: projectRoot,
            encoding: "utf8",
            shell: false,
        },
    );

    if (result.error) {
        throw result.error;
    }

    expect(result.status, result.stderr || result.stdout).toBe(0);

    return JSON.parse(result.stdout);
}

test("an authorized user can approve a purchase requisition once", async ({
    page,
    browserErrors,
    e2eData,
}) => {
    const requisitionId = e2eData.approvePurchaseRequisitionId;
    const approvePath = `/admin/purchase-requisitions/${requisitionId}/approve`;
    const showPath = `/admin/purchase-requisitions/${requisitionId}`;

    await loginThroughUi(page, e2eUsers.admin);
    await page.goto(showPath);
    await expect(
        page.getByText("Requested", { exact: true }).first(),
    ).toBeVisible();

    let continueRequest;
    let requestIntercepted;
    const intercepted = new Promise((resolveRequest) => {
        requestIntercepted = resolveRequest;
    });
    const continueGate = new Promise((resolveRequest) => {
        continueRequest = resolveRequest;
    });
    let approveRequests = 0;
    let documentRequests = 0;

    page.on("request", (request) => {
        if (request.resourceType() === "document") {
            documentRequests += 1;
        }
        if (
            new URL(request.url()).pathname === approvePath &&
            request.method() === "PATCH"
        ) {
            approveRequests += 1;
        }
    });
    await page.route(`**${approvePath}`, async (route) => {
        requestIntercepted();
        await continueGate;
        await route.continue();
    });

    const approveButton = page.getByRole("button", { name: "Approve" });
    await approveButton.click();
    const confirmation = page.getByRole("alertdialog", {
        name: "Approve requisition",
    });
    await confirmation.getByRole("button", { name: "Yes" }).click();
    await intercepted;
    await expect(approveButton).toBeDisabled();
    await expect(approveButton).toHaveClass(/p-button-loading/);

    const approveResponse = page.waitForResponse(
        (response) =>
            new URL(response.url()).pathname === approvePath &&
            response.request().method() === "PATCH",
    );
    continueRequest();
    expect((await approveResponse).status()).toBe(303);

    await expect(page).toHaveURL(new RegExp(`${showPath}$`));
    await expect(page.getByText("Approved", { exact: true })).toBeVisible();
    await expect(
        page.locator(".p-toast-message-success").filter({
            hasText: "Purchase requisition approved.",
        }),
    ).toBeVisible();
    expect(approveRequests).toBe(1);
    expect(documentRequests).toBe(0);
    expect(
        Number(
            scalarSql(
                "SELECT COUNT(*) FROM activity_log WHERE event = ? AND subject_type = ? AND subject_id = ?",
                [
                    "purchase_requisition_approved",
                    "App\\Models\\PurchaseRequisition",
                    requisitionId,
                ],
            ),
        ),
    ).toBe(1);
    expect(browserErrors).toBeDefined();
});

test("an approved requisition generates exactly one purchase order", async ({
    page,
    browserErrors,
    e2eData,
}) => {
    const requisitionId = e2eData.generatePurchaseRequisitionId;
    const generatePath = `/admin/purchase-requisitions/${requisitionId}/generate-purchase-order`;

    await loginThroughUi(page, e2eUsers.admin);
    await page.goto(`/admin/purchase-requisitions/${requisitionId}`);
    await expect(page.getByText("Approved", { exact: true })).toBeVisible();

    await page.getByRole("button", { name: "Generate Purchase Order" }).click();
    const dialog = page.getByRole("dialog", {
        name: "Generate Purchase Order",
    });
    await selectComboboxOptionMatching(
        page,
        dialog,
        "Supplier",
        /E2E-SUP - E2E Supplier Before Partial Reload/,
    );

    let continueRequest;
    let requestIntercepted;
    const intercepted = new Promise((resolveRequest) => {
        requestIntercepted = resolveRequest;
    });
    const continueGate = new Promise((resolveRequest) => {
        continueRequest = resolveRequest;
    });
    let generationRequests = 0;
    let documentRequests = 0;

    page.on("request", (request) => {
        if (request.resourceType() === "document") {
            documentRequests += 1;
        }
        if (
            new URL(request.url()).pathname === generatePath &&
            request.method() === "POST"
        ) {
            generationRequests += 1;
        }
    });
    await page.route(`**${generatePath}`, async (route) => {
        requestIntercepted();
        await continueGate;
        await route.continue();
    });

    const generateButton = dialog.getByRole("button", { name: "Generate" });
    await generateButton.click();
    await intercepted;
    await expect(generateButton).toBeDisabled();
    await expect(generateButton).toHaveClass(/p-button-loading/);

    const generationResponse = page.waitForResponse(
        (response) =>
            new URL(response.url()).pathname === generatePath &&
            response.request().method() === "POST",
    );
    continueRequest();
    const successToast = expect(
        page.locator(".p-toast-message-success").filter({
            hasText: "Purchase order generated.",
        }),
    ).toBeVisible({ timeout: 15_000 });
    expect((await generationResponse).status()).toBe(302);

    await successToast;
    await expect(page).toHaveURL(/\/admin\/purchase-orders\/\d+$/);
    await expect(
        page.getByText("E2E Supplier Before Partial Reload", { exact: true }),
    ).toBeVisible();
    await expect(page.getByText("E2E-MAT-001")).toBeVisible();
    expect(generationRequests).toBe(1);
    expect(documentRequests).toBe(0);
    expect(
        Number(
            scalarSql(
                "SELECT COUNT(*) FROM purchase_orders WHERE purchase_requisition_id = ?",
                [requisitionId],
            ),
        ),
    ).toBe(1);
    expect(
        Number(
            scalarSql(
                "SELECT COUNT(*) FROM purchase_order_items poi JOIN purchase_orders po ON po.id = poi.purchase_order_id WHERE po.purchase_requisition_id = ?",
                [requisitionId],
            ),
        ),
    ).toBe(1);
    expect(browserErrors).toBeDefined();
});
