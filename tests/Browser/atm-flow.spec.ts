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
    await expect(page.getByRole('status').filter({ hasText: 'Einzahlung erfolgreich gebucht.' })).toBeVisible();
    await page.getByRole('button', { name: 'Benachrichtigung schließen' }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Einzahlung erfolgreich gebucht.' })).toBeHidden();

    await page.getByRole('link', { name: 'Zurück zum Konto' }).click();
    await page.getByLabel('Auszahlungsbetrag in Euro').fill('2,5');
    await page.getByRole('button', { name: 'Demo-Auszahlung buchen' }).click();
    await expect(page).toHaveURL('/atm/session');
    await expect(page.getByRole('alert')).toContainText('ganzen Eurobetrag');

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

test('switches and persists themes and exposes showcase links', async ({ page }) => {
    await page.goto('/atm');

    await expect(page.getByRole('link', { name: 'GitHub' })).toHaveAttribute(
        'href',
        'https://github.com/WolfgangSiegert/laravel-atm-showcase',
    );
    await expect(page.getByRole('link', { name: 'Portfolio-Code' })).toHaveAttribute(
        'href',
        'https://github.com/WolfgangSiegert/WolfgangSiegert.github.io',
    );

    await page.getByRole('button', { name: 'Dunkel-Darstellung' }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await expect(page.getByRole('button', { name: 'Dunkel-Darstellung' })).toHaveAttribute('aria-pressed', 'true');

    await page.getByRole('button', { name: 'Retro-Darstellung' }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'retro');
});
