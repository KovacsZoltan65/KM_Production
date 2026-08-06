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

async function postReceipt(page, receiptId) {
    const actionPath = `/admin/goods-receipts/${receiptId}/post`;
    const showPath = `/admin/goods-receipts/${receiptId}`;
    let release;
    let intercepted;
    const interception = new Promise((resolveRequest) => {
        intercepted = resolveRequest;
    });
    const gate = new Promise((resolveRequest) => {
        release = resolveRequest;
    });
    let postRequests = 0;
    let documentRequests = 0;

    page.on("request", (request) => {
        if (request.resourceType() === "document") documentRequests += 1;
        if (
            new URL(request.url()).pathname === actionPath &&
            request.method() === "POST"
        ) {
            postRequests += 1;
        }
    });
    await page.route(`**${actionPath}`, async (route) => {
        intercepted();
        await gate;
        await route.continue();
    });

    const button = page.locator("#app").getByRole("button", {
        name: "Post Goods Receipt",
    });
    await button.click();
    await page
        .getByRole("alertdialog", { name: "Post goods receipt" })
        .getByRole("button", { name: "Yes" })
        .click();
    await interception;
    await expect(button).toBeDisabled();
    await expect(button).toHaveClass(/p-button-loading/);

    const response = page.waitForResponse(
        (candidate) =>
            new URL(candidate.url()).pathname === actionPath &&
            candidate.request().method() === "POST",
    );
    release();
    expect((await response).status()).toBe(302);
    await expect(page).toHaveURL(new RegExp(`${showPath}$`));
    await expect(page.getByText("Posted", { exact: true })).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Post Goods Receipt" }),
    ).toHaveCount(0);
    const successToast = page.locator(".p-toast-message-success").filter({
        hasText: "Goods receipt posted.",
    });
    await expect(successToast).toBeVisible();
    await expect(successToast).toHaveCount(1);
    expect(postRequests).toBe(1);
    expect(documentRequests).toBe(0);
}

test("posting a partial receipt updates inventory and the purchase order once", async ({
    page,
    e2eData,
}) => {
    const receiptId = e2eData.partialGoodsReceiptId;
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto(`/admin/goods-receipts/${receiptId}`);
    await expect(page.getByText("Draft", { exact: true })).toBeVisible();

    await postReceipt(page, receiptId);

    expect(
        Number(
            scalarSql(
                "SELECT quantity FROM stock_balances WHERE item_id = ? AND location_id = ?",
                [
                    e2eData.partialGoodsReceiptInventoryItemId,
                    e2eData.partialGoodsReceiptLocationId,
                ],
            ),
        ),
    ).toBe(4);
    expect(
        Number(
            scalarSql(
                "SELECT received_quantity FROM purchase_order_items WHERE id = ?",
                [e2eData.partialGoodsReceiptPurchaseOrderItemId],
            ),
        ),
    ).toBe(4);
    expect(
        scalarSql("SELECT status FROM purchase_orders WHERE id = ?", [
            e2eData.partialGoodsReceiptPurchaseOrderId,
        ]),
    ).toBe("partially_received");
    expect(
        Number(
            scalarSql(
                "SELECT COUNT(*) FROM stock_movements WHERE source_type = ? AND source_id = ? AND movement_type = ? AND quantity = ?",
                ["App\\Models\\GoodsReceipt", receiptId, "purchase_receive", 4],
            ),
        ),
    ).toBe(1);
});

test("posting a full receipt completes inventory and the purchase order once", async ({
    page,
    e2eData,
}) => {
    const receiptId = e2eData.fullGoodsReceiptId;
    await loginThroughUi(page, e2eUsers.admin);
    await page.goto(`/admin/goods-receipts/${receiptId}`);
    await expect(page.getByText("Draft", { exact: true })).toBeVisible();

    await postReceipt(page, receiptId);

    expect(
        Number(
            scalarSql(
                "SELECT quantity FROM stock_balances WHERE item_id = ? AND location_id = ?",
                [
                    e2eData.fullGoodsReceiptInventoryItemId,
                    e2eData.fullGoodsReceiptLocationId,
                ],
            ),
        ),
    ).toBe(6);
    expect(
        Number(
            scalarSql(
                "SELECT received_quantity FROM purchase_order_items WHERE id = ?",
                [e2eData.fullGoodsReceiptPurchaseOrderItemId],
            ),
        ),
    ).toBe(6);
    expect(
        scalarSql("SELECT status FROM purchase_orders WHERE id = ?", [
            e2eData.fullGoodsReceiptPurchaseOrderId,
        ]),
    ).toBe("received");
    expect(
        Number(
            scalarSql(
                "SELECT COUNT(*) FROM stock_movements WHERE source_type = ? AND source_id = ? AND movement_type = ? AND quantity = ?",
                ["App\\Models\\GoodsReceipt", receiptId, "purchase_receive", 6],
            ),
        ),
    ).toBe(1);
});
