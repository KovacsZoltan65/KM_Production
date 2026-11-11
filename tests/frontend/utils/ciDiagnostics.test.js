import { afterEach, describe, expect, it, vi } from "vitest";
import { resolve } from "node:path";
import { readFileSync } from "node:fs";
import CiPlaywrightReporter, {
    diagnosticPolicy,
    minimalTestResult,
    publishedPlaywrightReport,
} from "../../../scripts/ci-playwright-reporter.js";

afterEach(() => vi.restoreAllMocks());

describe("published CI diagnostics", () => {
    it("excludes unknown secrets, errors, attachments and arbitrary stdout", () => {
        const sentinel = `CI_SYNTHETIC_${crypto.randomUUID()}`;
        const outputParts = [];
        const publish = vi.fn((report) =>
            outputParts.push(JSON.stringify(report)),
        );
        const reporter = new CiPlaywrightReporter({
            stdout: (text) => outputParts.push(text),
            stderr: (text) => outputParts.push(text),
            publish,
        });
        const test = {
            id: sentinel,
            title: sentinel,
            location: {
                file: resolve("tests/e2e/helpers/auth.js"),
                line: 7,
            },
        };
        reporter.onStdOut(sentinel);
        reporter.onStdErr(sentinel);
        reporter.onError(new Error(sentinel));
        reporter.onTestEnd(test, {
            status: "failed",
            error: { message: sentinel },
            attachments: [{ body: sentinel }],
        });
        reporter.onEnd({ status: "failed", error: sentinel });
        const output = outputParts.join("");
        const leaked = output.includes(sentinel);
        expect(leaked).toBe(false);
        expect(reporter.tests[0].status).toBe("failed");
        expect(reporter.tests[0].file).toBe("tests/e2e/helpers/auth.js");
        expect(reporter.runnerErrors).toBe(1);
        expect(publish).toHaveBeenCalledTimes(1);
    });

    it("never records authentication or other browser payloads on CI", () => {
        expect(diagnosticPolicy(true)).toEqual({
            trace: "off",
            screenshot: "off",
            video: "off",
        });
        expect(diagnosticPolicy(false).trace).toBe("retain-on-failure");
    });

    it("rejects unknown paths and statuses and keeps deterministic case IDs", () => {
        const test = {
            id: "synthetic-case",
            location: { file: resolve("package.json"), line: -1 },
        };
        const entry = minimalTestResult(test, { status: "unrecognized" });
        expect(entry.file).toBe("tests/e2e");
        expect(entry.status).toBe("failed");
        expect(entry.line).toBe(1);
        expect(entry.id).toBe(minimalTestResult(test, { status: "failed" }).id);
    });

    it("uploads only the minimal summary and stores server logs privately", () => {
        const workflow = readFileSync(".github/workflows/frontend.yml", "utf8");
        const paths = [...workflow.matchAll(/^\s+path: (.+)$/gm)].map(
            (match) => match[1],
        );
        expect(paths).toEqual([
            publishedPlaywrightReport,
            publishedPlaywrightReport,
        ]);
        const config = readFileSync("playwright.config.js", "utf8");
        expect(/reporter:\s*process\.env\.CI\s*\?/.test(config)).toBe(true);
        expect(config.includes('"./scripts/ci-playwright-reporter.js"')).toBe(
            true,
        );
        const setup = readFileSync("scripts/e2e-global-setup.js", "utf8");
        expect(setup.includes('"test-results/e2e-server.log"')).toBe(false);
        expect(setup.includes("storage/framework/testing/e2e-server.log")).toBe(
            true,
        );
    });
});
