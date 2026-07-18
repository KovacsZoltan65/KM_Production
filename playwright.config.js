import { defineConfig, devices } from "@playwright/test";

const baseURL = process.env.E2E_BASE_URL || "http://127.0.0.1:8001";
const smokeMatch = /.*smoke\/(application|cross-browser|mobile)\.spec\.js/;

export default defineConfig({
    testDir: "./tests/e2e",
    globalSetup: "./scripts/e2e-global-setup.js",
    globalTeardown: "./scripts/e2e-global-teardown.js",
    fullyParallel: false,
    workers: 1,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    timeout: 60_000,
    expect: {
        timeout: 7_500,
    },
    reporter: process.env.CI
        ? [["line"], ["html", { open: "never" }]]
        : [["list"], ["html", { open: "never" }]],
    outputDir: "test-results",
    use: {
        baseURL,
        timezoneId: "Europe/Budapest",
        trace: "retain-on-failure",
        screenshot: "only-on-failure",
        video: "off",
    },
    projects: [
        {
            name: "chromium",
            use: { ...devices["Desktop Chrome"] },
        },
        {
            name: "firefox",
            testMatch: smokeMatch,
            use: { ...devices["Desktop Firefox"] },
        },
        {
            name: "webkit",
            testMatch: smokeMatch,
            use: { ...devices["Desktop Safari"] },
        },
        {
            name: "mobile-chromium",
            testMatch: /.*smoke\/mobile\.spec\.js/,
            use: { ...devices["Pixel 7"] },
        },
    ],
});
