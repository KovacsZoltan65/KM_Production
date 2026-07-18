import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { spawnSync } from "node:child_process";

const projectRoot = resolve(import.meta.dirname, "../../..");
const databasePath = resolve(projectRoot, "database/e2e.sqlite");
const fixtureDataPath = resolve(
    projectRoot,
    "storage/framework/testing/e2e-fixtures.json",
);

export function resetE2EFixtures() {
    const result = spawnSync(
        "php",
        [
            "artisan",
            "db:seed",
            "--class=Database\\Seeders\\E2ETestSeeder",
            "--env=e2e",
            "--force",
        ],
        {
            cwd: projectRoot,
            env: {
                ...process.env,
                APP_ENV: "e2e",
                DB_CONNECTION: "sqlite",
                DB_DATABASE: databasePath,
                FILESYSTEM_DISK: "e2e",
                SESSION_DRIVER: "database",
            },
            encoding: "utf8",
            shell: false,
        },
    );

    if (result.error) {
        throw result.error;
    }

    if (result.status !== 0) {
        throw new Error(
            `E2E fixture reset failed: ${result.stderr || result.stdout}`,
        );
    }

    return JSON.parse(readFileSync(fixtureDataPath, "utf8"));
}
