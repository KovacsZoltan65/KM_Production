export async function selectComboboxOption(page, scope, name, option) {
    await scope.getByRole("combobox", { name }).click();
    await page.getByRole("option", { name: option }).click();
}

export async function selectComboboxOptionMatching(page, scope, name, option) {
    await scope.getByRole("combobox", { name }).click();
    await page.getByRole("option", { name: option }).click();
}
