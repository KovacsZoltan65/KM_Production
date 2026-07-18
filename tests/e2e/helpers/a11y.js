import AxeBuilder from "@axe-core/playwright";
import { expect } from "./test.js";

export async function expectNoSeriousAccessibilityViolations(page, context) {
    const results = await new AxeBuilder({ page }).analyze();
    const blockingViolations = results.violations.filter((violation) =>
        ["serious", "critical"].includes(violation.impact),
    );

    expect(
        blockingViolations.map((violation) => ({
            id: violation.id,
            impact: violation.impact,
            help: violation.help,
            targets: violation.nodes.map((node) => node.target),
        })),
        `${context} accessibility violations`,
    ).toEqual([]);
}
