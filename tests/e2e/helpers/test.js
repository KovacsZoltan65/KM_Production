import { test as base, expect } from "@playwright/test";
import { resetE2EFixtures } from "./database.js";

export const test = base.extend({
    e2eData: [
        async ({ context }, use) => {
            await context.clearCookies();
            const fixtureData = resetE2EFixtures();
            await use(fixtureData);
        },
        { auto: true },
    ],
    browserErrors: async ({ page }, use) => {
        const errors = [];
        const allowedPatterns = [];

        page.on("pageerror", (error) => {
            errors.push(`pageerror: ${error.message}`);
        });

        page.on("console", (message) => {
            if (message.type() === "error") {
                errors.push(`console: ${message.text()}`);
            }
        });

        page.on("requestfailed", (request) => {
            if (
                ["document", "script", "stylesheet", "xhr", "fetch"].includes(
                    request.resourceType(),
                )
            ) {
                errors.push(
                    `requestfailed: ${request.method()} ${request.url()} ${request.failure()?.errorText || "unknown error"}`,
                );
            }
        });

        page.on("response", (response) => {
            const resourceType = response.request().resourceType();
            const applicationFailure =
                response.status() >= 400 &&
                ["document", "xhr", "fetch"].includes(resourceType) &&
                response
                    .url()
                    .startsWith(
                        process.env.E2E_BASE_URL || "http://127.0.0.1:8001",
                    );
            const criticalAssetFailure =
                response.status() >= 400 &&
                ["script", "stylesheet", "font"].includes(resourceType);

            if (applicationFailure || criticalAssetFailure) {
                errors.push(
                    `response: ${response.status()} ${response.request().method()} ${response.url()}`,
                );
            }
        });

        await use({
            allow: (pattern) => allowedPatterns.push(pattern),
        });

        const unexpectedErrors = errors.filter(
            (error) => !allowedPatterns.some((pattern) => pattern.test(error)),
        );
        expect(unexpectedErrors, "critical browser errors").toEqual([]);
    },
});

export { expect };
