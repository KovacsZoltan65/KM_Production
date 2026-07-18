import { spawnSync } from "node:child_process";
import { existsSync, readFileSync, renameSync, rmSync } from "node:fs";
import { resolve } from "node:path";

const projectRoot = resolve(import.meta.dirname, "..");
const pidPath = resolve(
    projectRoot,
    "storage/framework/testing/e2e-server.pid",
);
const viteHotPath = resolve(projectRoot, "public/hot");
const viteHotBackupPath = resolve(
    projectRoot,
    "storage/framework/testing/e2e-public-hot.backup",
);

function restoreViteHotFile() {
    if (existsSync(viteHotBackupPath) && !existsSync(viteHotPath)) {
        renameSync(viteHotBackupPath, viteHotPath);
    }
}

export default async function globalTeardown() {
    if (!existsSync(pidPath)) {
        restoreViteHotFile();
        return;
    }

    const pid = Number(readFileSync(pidPath, "utf8"));
    rmSync(pidPath, { force: true });

    if (!Number.isInteger(pid) || pid <= 0) {
        return;
    }

    if (process.platform === "win32") {
        spawnSync("taskkill", ["/PID", String(pid), "/T", "/F"], {
            stdio: "ignore",
            windowsHide: true,
        });
        restoreViteHotFile();
        return;
    }

    try {
        process.kill(pid, "SIGTERM");
    } catch {
        // The process already exited.
    }

    restoreViteHotFile();
}
