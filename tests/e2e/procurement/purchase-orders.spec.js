import { spawnSync } from "node:child_process";
import { resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { test, expect } from "../helpers/test.js";
import { loginThroughUi } from "../helpers/auth.js";
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
                "$statement->execute(json_decode($argv[3], true, 512, JSON_THROW_ON_ERROR));" +
                "echo json_encode($statement->fetchColumn(), JSON_THROW_ON_ERROR);",
            databasePath,
            sql,
            JSON.stringify(parameters),
        ],
        { cwd: projectRoot, encoding: "utf8", shell: false },
    );
    if (result.error) throw result.error;
    expect(result.status, result.stderr || result.stdout).toBe(0);
    return JSON.parse(result.stdout);
}

async function performWorkflowAction(
    page,
    id,
    action,
    dialogName,
    successText,
) {
    const actionPath = `/admin/purchase-orders/${id}/${action}`;
    const showPath = `/admin/purchase-orders/${id}`;
    let release;
    let intercepted;
    const interception = new Promise((resolveRequest) => {
        intercepted = resolveRequest;
    });
    const gate = new Promise((resolveRequest) => {
        release = resolveRequest;
    });
    let actionRequests = 0;
    let documentRequests = 0;

    page.on("request", (request) => {
        if (request.resourceType() === "document") documentRequests += 1;
        if (
            new URL(request.url()).pathname === actionPath &&
            request.method() === "PATCH"
        ) {
            actionRequests += 1;
        }
    });
    await page.route(`**${actionPath}`, async (route) => {
        intercepted();
        await gate;
        await route.continue();
    });

    const button = page.locator("#app").getByRole("button", {
        name: dialogName,
    });
    await button.click();
    await page
        .getByRole("alertdialog", { name: dialogName })
        .getByRole("button", { name: "Yes" })
        .click();
    await interception;
    await expect(button).toBeDisabled();
    await expect(button).toHaveClass(/p-button-loading/);

    const response = page.waitForResponse(
        (candidate) =>
            new URL(candidate.url()).pathname === actionPath &&
            candidate.request().method() === "PATCH",
    );
    release();
    expect((await response).status()).toBe(303);
    await expect(page).toHaveURL(new RegExp(`${showPath}$`));
    const successToast = page
        .locator(".p-toast-message-success")
        .filter({ hasText: successText });
    await expect(successToast).toBeVisible();
    await expect(successToast).toHaveCount(1);
    expect(actionRequests).toBe(1);
    expect(documentRequests).toBe(0);
}

test("an authorized user approves a purchase order exactly once", async ({
    page,
    e2eData,
}) => {
    const id = e2eData.approvePurchaseOrderId;
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto(`/admin/purchase-orders/${id}`);
    await expect(page.getByText("Draft", { exact: true })).toBeVisible();

    await performWorkflowAction(
        page,
        id,
        "approve",
        "Approve",
        "Purchase order approved.",
    );

    await expect(
        page.getByText("Ordered", { exact: true }).first(),
    ).toBeVisible();
    expect(
        Number(
            scalarSql(
                "SELECT COUNT(*) FROM activity_log WHERE event = ? AND subject_type = ? AND subject_id = ?",
                ["purchase_order_approved", "App\\Models\\PurchaseOrder", id],
            ),
        ),
    ).toBe(1);
});

test("an authorized user closes a purchase order exactly once", async ({
    page,
    e2eData,
}) => {
    const id = e2eData.closePurchaseOrderId;
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto(`/admin/purchase-orders/${id}`);
    await expect(
        page.getByText("Ordered", { exact: true }).first(),
    ).toBeVisible();

    await performWorkflowAction(
        page,
        id,
        "close",
        "Close",
        "Purchase order closed.",
    );

    await expect(
        page.getByText("Received", { exact: true }).first(),
    ).toBeVisible();
    await expect(page.getByRole("button", { name: "Close" })).toHaveCount(0);
    expect(
        Number(
            scalarSql(
                "SELECT COUNT(*) FROM activity_log WHERE event = ? AND subject_type = ? AND subject_id = ?",
                ["purchase_order_closed", "App\\Models\\PurchaseOrder", id],
            ),
        ),
    ).toBe(1);
});
