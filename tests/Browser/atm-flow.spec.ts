import { expect, test } from '@playwright/test';

test('completes the public ATM flow with isolated demo data', async ({ page }) => {
    await page.goto('/atm');
    await page.getByRole('link', { name: 'Karte auswählen' }).click();
    await expect(page.getByRole('complementary', { name: 'Öffentliche Demo-Zugänge' })).toContainText('DEMO-001 · PIN 1234');
    await page.getByRole('combobox', { name: 'Demo-Karte' }).selectOption({ label: 'DEMO-002' });

    for (const digit of ['9', '9', '9', '9']) {
        await page.getByRole('button', { name: `Ziffer ${digit}` }).click();
    }
    await page.getByRole('button', { name: 'Sitzung starten' }).click();
    await expect(page.getByRole('alert')).toContainText('Anmeldung nicht möglich');

    for (const digit of ['0', '0', '4', '2']) {
        await page.getByRole('button', { name: `Ziffer ${digit}` }).click();
    }
    await page.getByRole('button', { name: 'Sitzung starten' }).click();
    await expect(page.getByRole('heading', { name: 'Willkommen, Sam Beispiel.' })).toBeVisible();

    await page.getByLabel('Betrag in Euro', { exact: true }).fill('50');
    await page.getByLabel('Verwendungszweck', { exact: false }).nth(1).fill('Browser-Testeinzahlung');
    await page.getByRole('button', { name: 'Demo-Einzahlung buchen' }).click();
    await expect(page.getByRole('heading', { name: 'Demo-Einzahlung bestätigt.' })).toBeVisible();
    await expect(page.getByText('+50,00 €')).toBeVisible();

    await page.getByRole('link', { name: 'Zurück zum Konto' }).click();
    await page.getByLabel('Auszahlungsbetrag in Euro').fill('20');
    await page.getByLabel('Verwendungszweck', { exact: false }).first().fill('Browser-Testauszahlung');
    await page.getByRole('button', { name: 'Demo-Auszahlung buchen' }).click();
    await expect(page.getByRole('heading', { name: 'Demo-Auszahlung bestätigt.' })).toBeVisible();
    await expect(page.getByText('LERN-Bank Mein Geldautomat')).toBeVisible();

    await page.getByRole('link', { name: 'Zurück zum Konto' }).click();
    await expect(page.getByText('30,00 €', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Sitzung beenden & Karte zurückgeben' }).click();
    await expect(page.getByRole('status')).toContainText('Sitzung wurde beendet');
});

test('keeps PIN entry keyboard accessible and mobile width stable', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/atm/cards');
    const pin = page.getByLabel('PIN', { exact: true });
    await pin.focus();
    await page.keyboard.type('1234');
    await expect(pin).toHaveValue('1234');
    await page.keyboard.press('Backspace');
    await expect(pin).toHaveValue('123');
    await page.getByRole('button', { name: 'PIN löschen' }).click();
    await expect(pin).toHaveValue('');
    await expect(page.getByRole('group', { name: 'PIN-Nummernfeld' })).toBeVisible();

    const widths = await page.evaluate(() => ({ viewport: window.innerWidth, document: document.documentElement.scrollWidth }));
    expect(widths.document).toBe(widths.viewport);
});
