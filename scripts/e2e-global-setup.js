import { spawn } from "node:child_process";
import { existsSync, mkdirSync, renameSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";

const projectRoot = resolve(import.meta.dirname, "..");
const baseURL = process.env.E2E_BASE_URL || "http://127.0.0.1:8001";
const pidPath = resolve(
    projectRoot,
    "storage/framework/testing/e2e-server.pid",
);
const databasePath = resolve(projectRoot, "database/e2e.sqlite");
const viteHotPath = resolve(projectRoot, "public/hot");
const viteHotBackupPath = resolve(
    projectRoot,
    "storage/framework/testing/e2e-public-hot.backup",
);

async function waitForServer() {
    const deadline = Date.now() + 60_000;
    let lastError;

    while (Date.now() < deadline) {
        try {
            const response = await fetch(`${baseURL}/login`, {
                method: "HEAD",
            });

            if (response.ok) {
                return;
            }

            lastError = new Error(`Unexpected status ${response.status}`);
        } catch (error) {
            lastError = error;
        }

        await new Promise((resolveDelay) => setTimeout(resolveDelay, 500));
    }

    throw lastError || new Error("Timed out waiting for E2E server.");
}

export default async function globalSetup() {
    mkdirSync(dirname(pidPath), { recursive: true });

    if (existsSync(viteHotPath) && !existsSync(viteHotBackupPath)) {
        renameSync(viteHotPath, viteHotBackupPath);
    }

    const child = spawn(
        "php",
        [
            "-d",
            "xdebug.mode=off",
            "-S",
            "127.0.0.1:8001",
            "-t",
            "public",
            "scripts/e2e-server.php",
        ],
        {
            cwd: projectRoot,
            env: {
                ...process.env,
                APP_ENV: "e2e",
                APP_URL: baseURL,
                DB_CONNECTION: "sqlite",
                DB_DATABASE: databasePath,
                FILESYSTEM_DISK: "e2e",
                SESSION_DRIVER: "database",
            },
            stdio: "inherit",
            windowsHide: true,
        },
    );

    writeFileSync(pidPath, String(child.pid), "utf8");
    await waitForServer();
}
