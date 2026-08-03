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
        name: "shortages",
        path: "/admin/inventory/shortages",
        search: "E2E-SO-0001",
        initialText: "987.123",
        updatedText: "876.543",
        update: (fixtures) =>
            executeSql(
                "UPDATE material_requirements SET missing_quantity = ? WHERE id = ?",
                [876.543, fixtures.shortageId],
            ),
    },
    {
        name: "material requirements",
        path: "/admin/inventory/material-requirements",
        configureFilter: async (page) => {
            await page
                .getByRole("combobox", { name: "Required item", exact: true })
                .click();
            await page
                .getByRole("option", {
                    name: "E2E-MR-001 - E2E Material Requirement Item",
                    exact: true,
                })
                .click();
            await expect(page).toHaveURL(/required_item_id=\d+/);
        },
        assertFilter: async (page) => {
            await expect(
                page.getByRole("combobox", {
                    name: "E2E-MR-001 - E2E Material Requirement Item",
                    exact: true,
                }),
            ).toBeVisible();
        },
        initialText: "321.123",
        updatedText: "654.321",
        update: (fixtures) =>
            executeSql(
                "UPDATE material_requirements SET required_quantity = ? WHERE id = ?",
                [654.321, fixtures.materialRequirementId],
            ),
    },
    {
        name: "stock reservations",
        path: "/admin/inventory/stock-reservations",
        configureFilter: async (page) => {
            await page
                .getByRole("combobox", { name: "Status", exact: true })
                .click();
            await page
                .getByRole("option", { name: "Active", exact: true })
                .click();
            await expect(page).toHaveURL(/status=active/);
        },
        assertFilter: async (page) => {
            await expect(
                page.getByRole("combobox", { name: "Active", exact: true }),
            ).toBeVisible();
        },
        initialText: "E2E-STOCK-RESERVATION-PARTIAL-REFRESH",
        initialExact: false,
        updatedText: "45.678",
        update: (fixtures) =>
            executeSql(
                "UPDATE stock_reservations SET reserved_quantity = ? WHERE id = ?",
                [45.678, fixtures.reservationId],
            ),
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
    {
        name: "factory units",
        path: "/admin/factory-units",
        search: "E2E-FU",
        initialText: "E2E Factory Unit",
        updatedText: "E2E Factory Unit After Partial Reload",
        update: (fixtures) =>
            executeSql("UPDATE factory_units SET name = ? WHERE id = ?", [
                "E2E Factory Unit After Partial Reload",
                fixtures.factoryUnitId,
            ]),
    },
    {
        name: "locations",
        path: "/admin/locations",
        search: "E2E-LOC",
        initialText: "E2E Warehouse",
        updatedText: "E2E Location After Partial Reload",
        update: (fixtures) =>
            executeSql("UPDATE locations SET name = ? WHERE id = ?", [
                "E2E Location After Partial Reload",
                fixtures.locationId,
            ]),
    },
    {
        name: "professional roles",
        path: "/admin/professional-roles",
        search: "E2E-PRO",
        initialText: "E2E Professional Role Before Partial Reload",
        updatedText: "E2E Professional Role After Partial Reload",
        update: (fixtures) =>
            executeSql("UPDATE professional_roles SET name = ? WHERE id = ?", [
                "E2E Professional Role After Partial Reload",
                fixtures.professionalRoleId,
            ]),
    },
    {
        name: "operation types",
        path: "/admin/operation-types",
        search: "E2E Operation Type",
        initialText: "E2E Operation Type Before Partial Reload",
        updatedText: "E2E Operation Type After Partial Reload",
        update: (fixtures) =>
            executeSql("UPDATE operation_types SET name = ? WHERE id = ?", [
                "E2E Operation Type After Partial Reload",
                fixtures.operationTypeId,
            ]),
    },
    {
        name: "users",
        path: "/admin/users",
        search: "e2e-admin@example.test",
        initialText: "E2E Admin",
        updatedText: "E2E Admin After Partial Reload",
        update: (fixtures) =>
            executeSql("UPDATE users SET name = ? WHERE id = ?", [
                "E2E Admin After Partial Reload",
                fixtures.adminId,
            ]),
    },
    {
        name: "roles",
        path: "/admin/roles",
        search: "e2e-refresh-role",
        initialText: "e2e-refresh-role-before",
        updatedText: "e2e-refresh-role-after",
        update: (fixtures) =>
            executeSql("UPDATE roles SET name = ? WHERE id = ?", [
                "e2e-refresh-role-after",
                fixtures.roleId,
            ]),
    },
    {
        name: "permissions",
        path: "/admin/permissions",
        search: "e2e-refresh-permission",
        initialText: "e2e-refresh-permission-before",
        updatedText: "e2e-refresh-permission-after",
        update: (fixtures) =>
            executeSql("UPDATE permissions SET name = ? WHERE id = ?", [
                "e2e-refresh-permission-after",
                fixtures.permissionId,
            ]),
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

        if (pageDefinition.configureFilter) {
            await pageDefinition.configureFilter(page);
        } else {
            await page.getByPlaceholder("Search").fill(pageDefinition.search);
            await page
                .getByRole("button", { name: "Search", exact: true })
                .click();
            await expect(page).toHaveURL(
                new RegExp(
                    `search=${encodeURIComponent(pageDefinition.search)}`,
                ),
            );
        }

        const filteredUrl = page.url();
        const refreshButton = page.locator('[data-test="refresh-records"]');
        let documentRequests = 0;

        page.on("request", (request) => {
            if (request.resourceType() === "document") {
                documentRequests += 1;
            }
        });

        await expect(refreshButton).toBeVisible();
        await expect(
            page
                .getByText(pageDefinition.initialText, {
                    exact: pageDefinition.initialExact ?? true,
                })
                .first(),
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
        await expect(refreshButton).toHaveClass(/p-button-loading/);
        expect(page.url()).toBe(filteredUrl);
        if (pageDefinition.assertFilter) {
            await pageDefinition.assertFilter(page);
        } else {
            await expect(page.getByPlaceholder("Search")).toHaveValue(
                pageDefinition.search,
            );
        }

        releaseRequest();
        await expect(
            page.getByText(pageDefinition.updatedText, { exact: true }).first(),
        ).toBeVisible();
        await expect(refreshButton).toBeEnabled();
        expect(page.url()).toBe(filteredUrl);
        expect(documentRequests).toBe(0);
        if (pageDefinition.assertFilter) {
            await pageDefinition.assertFilter(page);
        } else {
            await expect(page.getByPlaceholder("Search")).toHaveValue(
                pageDefinition.search,
            );
        }
        expect(browserErrors).toBeDefined();
    });
}
