import { spawn, spawnSync } from "node:child_process";
import {
    closeSync,
    existsSync,
    mkdirSync,
    openSync,
    readFileSync,
    renameSync,
    rmSync,
    writeFileSync,
} from "node:fs";
import { dirname, resolve } from "node:path";

const projectRoot = resolve(import.meta.dirname, "..");
const baseURL = process.env.E2E_BASE_URL || "http://127.0.0.1:8001";
const serverURL = new URL(baseURL);
const readinessTimeout = Number(process.env.E2E_READINESS_TIMEOUT_MS || 60_000);
const pidPath = resolve(
    projectRoot,
    "storage/framework/testing/e2e-server.pid",
);
const databasePath = resolve(projectRoot, "database/e2e.sqlite");
const serverLogPath = resolve(projectRoot, "test-results/e2e-server.log");
const viteHotPath = resolve(projectRoot, "public/hot");
const viteHotBackupPath = resolve(
    projectRoot,
    "storage/framework/testing/e2e-public-hot.backup",
);
const manifestPaths = [
    resolve(projectRoot, "public/build/manifest.json"),
    resolve(projectRoot, "public/build/.vite/manifest.json"),
];

function e2eEnvironment() {
    return {
        ...process.env,
        APP_ENV: "e2e",
        APP_URL: baseURL,
        DB_CONNECTION: "sqlite",
        DB_DATABASE: databasePath,
        FILESYSTEM_DISK: "e2e",
        SESSION_DRIVER: "database",
    };
}

function assertSafeBaseURL() {
    if (
        serverURL.protocol !== "http:" ||
        !["127.0.0.1", "localhost", "::1"].includes(serverURL.hostname)
    ) {
        throw new Error(
            "Unsafe E2E base URL: only a local HTTP server is allowed.",
        );
    }

    if (
        !Number.isInteger(readinessTimeout) ||
        readinessTimeout < 1_000 ||
        readinessTimeout > 60_000
    ) {
        throw new Error(
            "E2E_READINESS_TIMEOUT_MS must be an integer between 1000 and 60000.",
        );
    }
}

function assertPreparedEnvironment() {
    if (!existsSync(databasePath)) {
        throw new Error(
            "E2E database is missing. Run npm run test:e2e:prepare first.",
        );
    }

    const manifestPath = manifestPaths.find(existsSync);
    if (!manifestPath) {
        throw new Error(
            "Built frontend manifest is missing. Run npm run build first.",
        );
    }

    JSON.parse(readFileSync(manifestPath, "utf8"));

    const migrationStatus = spawnSync(
        "php",
        ["artisan", "migrate:status", "--env=e2e"],
        {
            cwd: projectRoot,
            env: e2eEnvironment(),
            encoding: "utf8",
            shell: false,
            windowsHide: true,
        },
    );

    if (
        migrationStatus.error ||
        migrationStatus.status !== 0 ||
        /\bPending\b/i.test(migrationStatus.stdout)
    ) {
        throw new Error(
            `E2E database readiness failed:\n${migrationStatus.stderr || migrationStatus.stdout || migrationStatus.error?.message}`,
        );
    }
}

async function waitForServer() {
    const deadline = Date.now() + readinessTimeout;
    let lastError;

    while (Date.now() < deadline) {
        try {
            const health = await fetch(`${baseURL}/up`);
            if (health.status !== 200) {
                throw new Error(
                    `Health endpoint returned HTTP ${health.status}.`,
                );
            }

            const login = await fetch(`${baseURL}/login`);
            if (login.status !== 200) {
                throw new Error(`Login page returned HTTP ${login.status}.`);
            }

            const html = await login.text();
            const assetPath = html.match(
                /(?:src|href)=["']([^"']*\/build\/assets\/[^"']+)["']/,
            )?.[1];
            if (!assetPath) {
                throw new Error(
                    "Login page did not reference a production-built asset.",
                );
            }

            const asset = await fetch(new URL(assetPath, baseURL));
            if (asset.status !== 200) {
                throw new Error(
                    `Frontend asset returned HTTP ${asset.status}: ${assetPath}`,
                );
            }

            return;
        } catch (error) {
            lastError = error;
        }

        await new Promise((resolveDelay) => setTimeout(resolveDelay, 250));
    }

    throw new Error(
        `E2E server readiness timed out for ${baseURL}: ${lastError?.message || "unknown error"}. See test-results/e2e-server.log.`,
    );
}

function terminateServer(pid) {
    if (!Number.isInteger(pid) || pid <= 0) {
        return;
    }

    if (process.platform === "win32") {
        spawnSync("taskkill", ["/PID", String(pid), "/T", "/F"], {
            stdio: "ignore",
            windowsHide: true,
        });
        return;
    }

    try {
        process.kill(pid, "SIGTERM");
    } catch {
        // The process already exited.
    }
}

function restoreViteHotFile() {
    if (existsSync(viteHotBackupPath) && !existsSync(viteHotPath)) {
        renameSync(viteHotBackupPath, viteHotPath);
    }
}

export default async function globalSetup() {
    assertSafeBaseURL();
    assertPreparedEnvironment();

    mkdirSync(dirname(pidPath), { recursive: true });
    mkdirSync(dirname(serverLogPath), { recursive: true });
    writeFileSync(serverLogPath, "", "utf8");
    if (existsSync(viteHotPath) && !existsSync(viteHotBackupPath)) {
        renameSync(viteHotPath, viteHotBackupPath);
    }

    if (process.env.E2E_SKIP_SERVER_START === "1") {
        try {
            await waitForServer();
        } catch (error) {
            restoreViteHotFile();
            throw error;
        }
        return;
    }

    const logDescriptor = openSync(serverLogPath, "w");
    const port =
        serverURL.port || (serverURL.protocol === "https:" ? "443" : "80");
    const host =
        serverURL.hostname === "localhost" ? "127.0.0.1" : serverURL.hostname;
    const child = spawn(
        "php",
        [
            "-d",
            "xdebug.mode=off",
            "-S",
            `${host}:${port}`,
            "-t",
            "public",
            "scripts/e2e-server.php",
        ],
        {
            cwd: projectRoot,
            env: e2eEnvironment(),
            detached: process.platform !== "win32",
            stdio: ["ignore", logDescriptor, logDescriptor],
            windowsHide: true,
        },
    );
    closeSync(logDescriptor);

    writeFileSync(pidPath, String(child.pid), "utf8");

    try {
        await waitForServer();
    } catch (error) {
        terminateServer(child.pid);
        rmSync(pidPath, { force: true });
        restoreViteHotFile();
        throw error;
    }
}
