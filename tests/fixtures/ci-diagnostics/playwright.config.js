import { defineConfig } from "@playwright/test";

// No application, browser fixture, server, database or migration is involved.
export default defineConfig({
    testDir: ".",
    testMatch: "synthetic.spec.js",
    workers: 1,
    retries: 0,
    reporter: [["../../../scripts/ci-playwright-reporter.js"]],
    outputDir: "../../../test-results/ci-diagnostics-probe",
});
