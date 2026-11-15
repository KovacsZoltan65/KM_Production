import { test } from "@playwright/test";

const sentinel = process.env.CI_DIAGNOSTIC_SENTINEL;

test("synthetic successful result", async () => {});

test(`synthetic failure ${sentinel}`, async ({}, testInfo) => {
    console.log(sentinel);
    console.error(sentinel);
    await testInfo.attach("synthetic", { body: Buffer.from(sentinel) });
    throw new Error(sentinel);
});
