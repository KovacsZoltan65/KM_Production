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

function executeSql(sql, parameters) {
    const result = spawnSync(
        "php",
        [
            "-r",
            '$database = new PDO("sqlite:" . $argv[1]);' +
                "$statement = $database->prepare($argv[2]);" +
                "$parameters = json_decode($argv[3], true, 512, JSON_THROW_ON_ERROR);" +
                "$statement->execute($parameters);",
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
}

const pages = [
    {
        name: "items",
        path: "/admin/items",
        search: "E2E-MAT-001",
        initialText: "E2E Test Material",
        updatedText: "E2E Item After Partial Reload",
        update: (fixtures) =>
            executeSql("UPDATE items SET name = ? WHERE id = ?", [
                "E2E Item After Partial Reload",
                fixtures.itemId,
            ]),
    },
    {
        name: "customers",
        path: "/admin/customers",
        search: "E2E-CUST",
        initialText: "E2E Customer",
        updatedText: "E2E Customer After Partial Reload",
        update: (fixtures) =>
            executeSql("UPDATE customers SET name = ? WHERE id = ?", [
                "E2E Customer After Partial Reload",
                fixtures.customerId,
            ]),
    },
    {
        name: "suppliers",
        path: "/admin/suppliers",
        search: "E2E-SUP",
        initialText: "E2E Supplier Before Partial Reload",
        updatedText: "E2E Supplier After Partial Reload",
        update: (fixtures) =>
            executeSql("UPDATE suppliers SET name = ? WHERE id = ?", [
                "E2E Supplier After Partial Reload",
                fixtures.supplierId,
            ]),
    },
    {
        name: "stock balances",
        path: "/admin/inventory/stock-balances",
        search: "E2E-MAT-001",
        initialText: "12.000",
        updatedText: "77.000",
        update: (fixtures) =>
            executeSql("UPDATE stock_balances SET quantity = ? WHERE id = ?", [
                77,
                fixtures.stockBalanceId,
            ]),
    },
    {
        name: "customer orders",
        path: "/admin/customer-orders",
        search: "E2E-CUST",
        initialText: "2027-02-01",
        updatedText: "2027-03-15",
        update: (fixtures) =>
            executeSql(
                "UPDATE customer_orders SET requested_delivery_date = ? WHERE id = ?",
                ["2027-03-15", fixtures.customerOrderId],
            ),
    },
];

for (const pageDefinition of pages) {
    test(`${pageDefinition.name} records can be refreshed without navigation`, async ({
        page,
        e2eData,
        browserErrors,
    }) => {
        await loginThroughUi(page, e2eUsers.admin);
        await page.goto(pageDefinition.path);

        await page.getByPlaceholder("Search").fill(pageDefinition.search);
        await page.getByRole("button", { name: "Search", exact: true }).click();
        await expect(page).toHaveURL(
            new RegExp(`search=${encodeURIComponent(pageDefinition.search)}`),
        );

        const filteredUrl = page.url();
        const refreshButton = page.locator('[data-test="refresh-records"]');

        await expect(refreshButton).toBeVisible();
        await expect(
            page.getByText(pageDefinition.initialText, { exact: true }).first(),
        ).toBeVisible();
        pageDefinition.update(e2eData);

        let releaseRequest;
        const requestReleased = new Promise((resolveRequest) => {
            releaseRequest = resolveRequest;
        });
        const partialRequest = page.waitForRequest(
            (request) =>
                new URL(request.url()).pathname === pageDefinition.path &&
                request.headers()["x-inertia-partial-data"] === "records",
        );

        await page.route(
            (url) => url.pathname === pageDefinition.path,
            async (route) => {
                if (
                    route.request().headers()["x-inertia-partial-data"] ===
                    "records"
                ) {
                    await requestReleased;
                }
                await route.continue();
            },
        );

        await refreshButton.click();
        const request = await partialRequest;
        expect(["xhr", "fetch"]).toContain(request.resourceType());
        await expect(refreshButton).toBeDisabled();
        expect(page.url()).toBe(filteredUrl);
        await expect(page.getByPlaceholder("Search")).toHaveValue(
            pageDefinition.search,
        );

        releaseRequest();
        await expect(
            page.getByText(pageDefinition.updatedText, { exact: true }).first(),
        ).toBeVisible();
        await expect(refreshButton).toBeEnabled();
        expect(page.url()).toBe(filteredUrl);
        await expect(page.getByPlaceholder("Search")).toHaveValue(
            pageDefinition.search,
        );
        expect(browserErrors).toBeDefined();
    });
}
