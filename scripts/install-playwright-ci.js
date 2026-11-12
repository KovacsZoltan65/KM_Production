import { spawn } from "node:child_process";
import { setTimeout as delay } from "node:timers/promises";
import { fileURLToPath } from "node:url";
import { resolve } from "node:path";

export const installAttempts = 3;

export function installationSignals(text) {
    return {
        transient:
            /Temporary failure resolving|Could not resolve (?:host|proxy)|getaddrinfo (?:EAI_AGAIN|ENOTFOUND)|\bEAI_AGAIN\b|\bECONNRESET\b|\bETIMEDOUT\b|Connection timed out|502 Bad Gateway|503 Service Unavailable|504 Gateway Timeout/i.test(
                text,
            ),
        permanent:
            /Unable to locate package|has no installation candidate|unmet dependencies|Permission denied|\bEACCES\b|\bENOSPC\b|certificate|checksum|hash sum mismatch|integrity check failed|unknown option|unsupported (?:platform|operating system)|Host system is missing dependencies|error while loading shared libraries|dpkg:\s*error/i.test(
                text,
            ),
    };
}

export function installationArguments(browsers) {
    if (
        ![["chromium"], ["chromium", "firefox", "webkit"]].some(
            (allowed) =>
                allowed.length === browsers.length &&
                allowed.every((browser, index) => browser === browsers[index]),
        )
    ) {
        throw new Error("Unsupported CI browser selection.");
    }
    return ["install", "--with-deps", ...browsers];
}

export function runInstaller(args, launch = spawn) {
    return new Promise((fulfill) => {
        const child = launch(
            process.execPath,
            [resolve("node_modules/@playwright/test/cli.js"), ...args],
            { stdio: ["ignore", "pipe", "pipe"], windowsHide: true },
        );
        let tail = "";
        let transient = false;
        let permanent = false;
        const inspect = (chunk) => {
            const text = tail + chunk.toString();
            const signals = installationSignals(text);
            transient ||= signals.transient;
            permanent ||= signals.permanent;
            // Detect messages split across chunks without retaining raw logs.
            tail = text.slice(-512);
        };
        child.stdout.on("data", inspect);
        child.stderr.on("data", inspect);
        child.on("error", () =>
            fulfill({ code: 1, transient: false, permanent: true }),
        );
        child.on("close", (code, signal) =>
            fulfill({
                code: code ?? (signal === "SIGINT" ? 130 : 143),
                transient,
                permanent,
                terminated: Boolean(signal),
            }),
        );
    });
}

export async function installWithRetry(
    browsers,
    {
        run = runInstaller,
        sleep = delay,
        write = (text) => process.stdout.write(text),
    } = {},
) {
    const args = installationArguments(browsers);
    for (let attempt = 1; attempt <= installAttempts; attempt++) {
        write(
            `Playwright installation attempt ${attempt}/${installAttempts}.\n`,
        );
        const result = await run(args);
        if (result.code === 0) {
            write("Playwright browsers and system dependencies installed.\n");
            return 0;
        }
        const retryable =
            result.transient && !result.permanent && !result.terminated;
        const category = result.terminated
            ? "terminated"
            : retryable
              ? "transient-network"
              : "non-retryable";
        write(
            `Playwright installation failed: category=${category}, exit=${result.code}; raw diagnostics withheld.\n`,
        );
        if (!retryable || attempt === installAttempts) {
            return result.code;
        }
        const waitMs = attempt * 10_000;
        write(`Retrying installation in ${waitMs / 1000} seconds.\n`);
        await sleep(waitMs);
    }
}

if (resolve(process.argv[1] ?? "") === fileURLToPath(import.meta.url)) {
    try {
        process.exitCode = await installWithRetry(process.argv.slice(2));
    } catch {
        process.stderr.write(
            "Playwright installation wrapper failed; raw diagnostics withheld.\n",
        );
        process.exitCode = 1;
    }
}
