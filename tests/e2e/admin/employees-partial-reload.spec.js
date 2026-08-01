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

function updateEmployeeName(employeeId, name) {
    const result = spawnSync(
        "php",
        [
            "-r",
            '$database = new PDO("sqlite:" . $argv[1]);' +
                '$statement = $database->prepare("UPDATE employees SET name = ? WHERE id = ?");' +
                "$statement->execute([$argv[3], $argv[2]]);",
            databasePath,
            String(employeeId),
            name,
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

test("employees can be refreshed in place with a partial Inertia reload", async ({
    page,
    e2eData,
    browserErrors,
}) => {
    await loginThroughUi(page, e2eUsers.admin);
    const initialName = "E2E Employee Before Partial Reload";
    const updatedName = "E2E Externally Updated Employee";

    updateEmployeeName(e2eData.employeeId, initialName);
    await page.goto("/admin/employees");

    const refreshButton = page.locator('[data-test="refresh-records"]');

    await page.getByPlaceholder("Search").fill("EMP-WELDER-001");
    await page.getByRole("button", { name: "Search", exact: true }).click();
    await expect(page).toHaveURL(/search=EMP-WELDER-001/);

    const filteredUrl = page.url();

    await expect(refreshButton).toBeVisible();
    await expect(page.getByText(initialName, { exact: true })).toBeVisible();
    await expect(page.getByText(updatedName, { exact: true })).toHaveCount(0);
    updateEmployeeName(e2eData.employeeId, updatedName);

    let releaseRequest;
    const requestReleased = new Promise((resolveRequest) => {
        releaseRequest = resolveRequest;
    });
    const partialRequest = page.waitForRequest(
        (request) =>
            new URL(request.url()).pathname === "/admin/employees" &&
            request.headers()["x-inertia-partial-data"] === "records",
    );

    await page.route("**/admin/employees", async (route) => {
        if (route.request().headers()["x-inertia-partial-data"] === "records") {
            await requestReleased;
        }
        await route.continue();
    });

    await refreshButton.click();
    await partialRequest;
    await expect(refreshButton).toBeDisabled();
    expect(page.url()).toBe(filteredUrl);
    await expect(page.getByPlaceholder("Search")).toHaveValue("EMP-WELDER-001");

    releaseRequest();
    await expect(page.getByText(updatedName, { exact: true })).toBeVisible();
    await expect(refreshButton).toBeEnabled();
    expect(page.url()).toBe(filteredUrl);
    await expect(page.getByPlaceholder("Search")).toHaveValue("EMP-WELDER-001");
    expect(browserErrors).toBeDefined();
});
