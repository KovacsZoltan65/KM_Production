import { describe, expect, it, vi } from "vitest";
import { EventEmitter } from "node:events";
import { PassThrough } from "node:stream";
import { readFileSync } from "node:fs";
import prettier from "prettier";
import {
    installationArguments,
    installationSignals,
    installWithRetry,
    runInstaller,
} from "../../../scripts/install-playwright-ci.js";

describe("bounded Playwright installation retry", () => {
    it.each([["chromium"], ["chromium", "firefox", "webkit"]])(
        "retries a transient DNS failure and preserves the browser selection (%j)",
        async (...browsers) => {
            const run = vi
                .fn()
                .mockResolvedValueOnce({ code: 1, transient: true })
                .mockResolvedValueOnce({ code: 0 });
            const sleep = vi.fn();
            const write = vi.fn();
            expect(
                await installWithRetry(browsers, { run, sleep, write }),
            ).toBe(0);
            expect(run).toHaveBeenCalledTimes(2);
            for (const [args] of run.mock.calls) {
                expect(args).toEqual(["install", "--with-deps", ...browsers]);
            }
            expect(sleep).toHaveBeenCalledExactlyOnceWith(10_000);
        },
    );

    it("stops at three attempts and returns the final failure status", async () => {
        const run = vi
            .fn()
            .mockResolvedValueOnce({ code: 1, transient: true })
            .mockResolvedValueOnce({ code: 2, transient: true })
            .mockResolvedValueOnce({ code: 7, transient: true });
        const sleep = vi.fn();
        expect(
            await installWithRetry(["chromium"], {
                run,
                sleep,
                write: vi.fn(),
            }),
        ).toBe(7);
        expect(run).toHaveBeenCalledTimes(3);
        expect(sleep.mock.calls).toEqual([[10_000], [20_000]]);
    });

    it.each([
        { code: 9, transient: false },
        { code: 1, transient: true, permanent: true },
        { code: 130, transient: true, terminated: true },
        { code: 143, transient: true, terminated: true },
    ])(
        "does not retry non-network failures or termination (%j)",
        async (result) => {
            const run = vi.fn().mockResolvedValue(result);
            const sleep = vi.fn();
            expect(
                await installWithRetry(["chromium"], {
                    run,
                    sleep,
                    write: vi.fn(),
                }),
            ).toBe(result.code);
            expect(run).toHaveBeenCalledTimes(1);
            expect(sleep).not.toHaveBeenCalled();
        },
    );

    it("does not wait or retry after first-attempt success", async () => {
        const run = vi.fn().mockResolvedValue({ code: 0 });
        const sleep = vi.fn();
        expect(
            await installWithRetry(["chromium"], {
                run,
                sleep,
                write: vi.fn(),
            }),
        ).toBe(0);
        expect(run).toHaveBeenCalledTimes(1);
        expect(sleep).not.toHaveBeenCalled();
    });

    it.each([
        "Temporary failure resolving 'security.ubuntu.com'",
        "getaddrinfo EAI_AGAIN",
        "connect ETIMEDOUT",
        "read ECONNRESET",
        "503 Service Unavailable",
    ])("recognizes transient network diagnostics (%s)", (text) => {
        expect(installationSignals(text)).toEqual({
            transient: true,
            permanent: false,
        });
    });

    it.each([
        "Unable to locate package libexample",
        "Host system is missing dependencies",
        "certificate verify failed",
        "dpkg: error processing package",
        "Permission denied",
        "unknown option --bad",
    ])("keeps permanent installation errors non-retryable (%s)", (text) => {
        expect(installationSignals(text).permanent).toBe(true);
    });

    it("classifies chunked child output without publishing raw content", async () => {
        const marker = `CI_SYNTHETIC_${crypto.randomUUID()}`;
        const launch = vi.fn(() => {
            const child = new EventEmitter();
            child.stdout = new PassThrough();
            child.stderr = new PassThrough();
            queueMicrotask(() => {
                child.stdout.write("Temporary failure resol");
                child.stdout.write("ving 'security.ubuntu.com'\n");
                child.stderr.write(marker);
                child.emit("close", 1, null);
            });
            return child;
        });
        let published = "";
        const code = await installWithRetry(["chromium"], {
            run: (args) => runInstaller(args, launch),
            sleep: vi.fn(),
            write: (text) => (published += text),
        });
        expect(code).toBe(1);
        expect(launch).toHaveBeenCalledTimes(3);
        expect(published.includes(marker)).toBe(false);
        expect(published).toContain("category=transient-network");
        const [binary, args] = launch.mock.calls[0];
        expect(binary).toBe(process.execPath);
        expect(args.slice(1)).toEqual(["install", "--with-deps", "chromium"]);
    });

    it.each([
        [],
        ["firefox"],
        ["test"],
        ["chromium", "--skip-browser-download"],
    ])(
        "rejects unsupported selections before starting a process (%j)",
        (...browsers) => {
            expect(() => installationArguments(browsers)).toThrow(
                "Unsupported CI browser selection.",
            );
        },
    );
});

const field = (mapping, name) =>
    mapping.children.find(
        (entry) => entry.children[0].children[0].value === name,
    )?.children[1].children[0];

describe("Playwright workflow boundaries", () => {
    it("validates YAML and bounds only installation while preserving CI security", async () => {
        const text = readFileSync(".github/workflows/frontend.yml", "utf8");
        const { ast } = await prettier.__debug.parse(text, { parser: "yaml" });
        const root = ast.children[0].children.find(
            (node) => node.type === "documentBody",
        ).children[0];
        const jobs = field(root, "jobs");
        const installCommands = {
            "e2e-chromium": "node scripts/install-playwright-ci.js chromium",
            "e2e-cross-browser":
                "node scripts/install-playwright-ci.js chromium firefox webkit",
        };
        for (const [key, command] of Object.entries(installCommands)) {
            const job = field(jobs, key);
            expect(field(job, "runs-on").value).toBe("ubuntu-latest");
            expect(String(field(job, "timeout-minutes").value)).toBe("35");
            const steps = field(job, "steps").children.map(
                (node) => node.children[0],
            );
            const installation = steps.find(
                (step) => field(step, "run")?.value === command,
            );
            expect(String(field(installation, "timeout-minutes").value)).toBe(
                "12",
            );
            expect(field(installation, "continue-on-error")).toBeUndefined();
        }
        const actions = [...text.matchAll(/uses: (\S+)/g)].map((m) => m[1]);
        expect(actions.every((action) => /@[a-f0-9]{40}$/.test(action))).toBe(
            true,
        );
        expect(text.match(/persist-credentials: false/g)).toHaveLength(6);
        expect(
            text.match(
                /path: test-results\/published\/playwright-summary.json/g,
            ),
        ).toHaveLength(2);
        expect(text).toContain("npx playwright test --project=chromium");
        expect(text).toContain(
            "npx playwright test --project=firefox --project=webkit --project=mobile-chromium",
        );
    });
});
