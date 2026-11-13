import { expect } from "@playwright/test";

export async function selectComboboxOption(page, scope, name, option) {
    await scope.getByRole("combobox", { name }).click();
    await page.getByRole("option", { name: option }).click();
}

export async function selectComboboxOptionMatching(page, scope, name, option) {
    await scope.getByRole("combobox", { name }).click();
    await page.getByRole("option", { name: option }).click();
}
// PrimeVue DatePicker keyboard contract: Escape closes its popup.
// https://primevue.org/datepicker/#accessibility
export async function fillDatePicker(page, scope, label, value) {
    const input = scope.getByLabel(label, { exact: true });
    await input.fill(value);
    const popupId = await input.getAttribute("aria-controls");
    await input.press("Escape");
    await expect(input).toHaveAttribute("aria-expanded", "false");
    if (popupId) {
        await expect(
            page.locator(`[id=${JSON.stringify(popupId)}]`),
        ).toBeHidden();
    }
    await expect(input).toHaveValue(value);
}
