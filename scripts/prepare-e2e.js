import {
    copyFileSync,
    existsSync,
    mkdirSync,
    readFileSync,
    writeFileSync,
} from "node:fs";
import { resolve } from "node:path";
import { spawnSync } from "node:child_process";

const projectRoot = resolve(import.meta.dirname, "..");
const environmentExample = resolve(projectRoot, ".env.e2e.example");
const environmentFile = resolve(projectRoot, ".env.e2e");
const databaseFile = resolve(projectRoot, "database", "e2e.sqlite");

if (!existsSync(environmentFile)) {
    copyFileSync(environmentExample, environmentFile);
}

const environmentContents = readFileSync(environmentFile, "utf8");
const setting = (name) => {
    const match = environmentContents.match(new RegExp(`^${name}=(.*)$`, "m"));
    return match?.[1]?.trim().replace(/^['"]|['"]$/g, "") ?? "";
};

if (setting("APP_ENV") !== "e2e") {
    throw new Error("Unsafe E2E environment: APP_ENV must be e2e.");
}

if (setting("DB_CONNECTION") !== "sqlite") {
    throw new Error("Unsafe E2E environment: DB_CONNECTION must be sqlite.");
}

const configuredDatabase = resolve(projectRoot, setting("DB_DATABASE"));
if (configuredDatabase !== databaseFile) {
    throw new Error(
        "Unsafe E2E environment: DB_DATABASE must be database/e2e.sqlite.",
    );
}

mkdirSync(resolve(projectRoot, "database"), { recursive: true });
if (!existsSync(databaseFile)) {
    writeFileSync(databaseFile, "");
}

const safeEnvironment = {
    ...process.env,
    APP_ENV: "e2e",
    DB_CONNECTION: "sqlite",
    DB_DATABASE: databaseFile,
    FILESYSTEM_DISK: "e2e",
    SESSION_DRIVER: "database",
};
delete safeEnvironment.APP_KEY;

const runArtisan = (arguments_) => {
    const result = spawnSync("php", ["artisan", ...arguments_], {
        cwd: projectRoot,
        env: safeEnvironment,
        stdio: "inherit",
        shell: false,
    });

    if (result.error) {
        throw result.error;
    }

    if (result.status !== 0) {
        throw new Error(
            `php artisan ${arguments_.join(" ")} failed with exit code ${result.status}.`,
        );
    }
};

runArtisan(["key:generate", "--env=e2e", "--force"]);
runArtisan([
    "migrate:fresh",
    "--env=e2e",
    "--seed",
    "--seeder=Database\\Seeders\\E2ETestSeeder",
    "--force",
]);
