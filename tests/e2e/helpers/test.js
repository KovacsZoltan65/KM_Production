import { test as base, expect } from "@playwright/test";

export const test = base.extend({
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
            const criticalAssetFailure =
                response.status() >= 400 &&
                ["script", "stylesheet", "font"].includes(resourceType);

            if (response.status() >= 500 || criticalAssetFailure) {
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
