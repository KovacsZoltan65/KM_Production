import { createHash } from "node:crypto";
import { existsSync, mkdirSync, realpathSync, writeFileSync } from "node:fs";
import { dirname, relative, resolve } from "node:path";

export const publishedPlaywrightReport =
    "test-results/published/playwright-summary.json";

export function diagnosticPolicy(ci) {
    return {
        trace: ci ? "off" : "retain-on-failure",
        screenshot: ci ? "off" : "only-on-failure",
        video: ci ? "off" : "retain-on-failure",
    };
}

const statuses = new Set([
    "passed",
    "failed",
    "timedOut",
    "skipped",
    "interrupted",
]);

export function minimalTestResult(test, result, root = process.cwd()) {
    const candidate = resolve(test.location.file);
    const file = existsSync(candidate)
        ? relative(realpathSync(root), realpathSync(candidate)).replaceAll(
              "\\",
              "/",
          )
        : "";
    const safeFile = /^tests\/e2e\/[A-Za-z0-9_./-]+\.(js|ts)$/.test(file)
        ? file
        : "tests/e2e";
    const id = createHash("sha256").update(test.id).digest("hex").slice(0, 24);
    return {
        id: `case-${id}`,
        file: safeFile,
        line: Number.isInteger(test.location.line)
            ? Math.max(1, Math.min(1_000_000, test.location.line))
            : 1,
        status: statuses.has(result.status) ? result.status : "failed",
    };
}

export function publishedSummary(tests, status, runnerErrors) {
    return {
        status: statuses.has(status) ? status : "failed",
        runnerErrors,
        tests,
    };
}

export default class CiPlaywrightReporter {
    constructor(options = {}) {
        this.tests = [];
        this.runnerErrors = 0;
        this.stdout = options.stdout ?? ((text) => process.stdout.write(text));
        this.stderr = options.stderr ?? ((text) => process.stderr.write(text));
        this.publish =
            options.publish ??
            ((report) => {
                const output = resolve(publishedPlaywrightReport);
                mkdirSync(dirname(output), { recursive: true });
                writeFileSync(output, `${JSON.stringify(report, null, 2)}\n`, {
                    mode: 0o600,
                });
            });
    }

    onTestEnd(test, result) {
        const entry = minimalTestResult(test, result);
        this.tests.push(entry);
        this.stdout(
            `[${entry.status}] ${entry.id} ${entry.file}:${entry.line}\n`,
        );
    }

    // Browser/test output and arbitrary runner errors may contain unknown secrets.
    onStdOut() {}
    onStdErr() {}

    onError() {
        this.runnerErrors++;
        this.stderr("Playwright runner error; raw details omitted.\n");
    }

    onEnd(result) {
        const report = publishedSummary(
            this.tests,
            result.status,
            this.runnerErrors,
        );
        this.publish(report);
        this.stdout(
            `Playwright summary: ${report.status}, ${this.tests.length} tests, ${this.runnerErrors} runner errors.\n`,
        );
    }
}
