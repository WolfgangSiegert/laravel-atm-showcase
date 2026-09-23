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

test('offers classic ATM and touchscreen menu interfaces', async ({ page }) => {
    await page.goto('/atm');
    await page.getByRole('button', { name: 'Klassik-ATM-Darstellung' }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'classic');
    await expect(page.getByText('KLASSISCHER GELDAUTOMAT')).toBeVisible();

    await page.getByRole('link', { name: 'Karte auswählen' }).click();
    await page.getByRole('radio', { name: /DEMO-001/ }).click();
    for (const digit of ['1', '2', '3', '4']) {
        await page.getByRole('button', { name: `Ziffer ${digit}` }).click();
    }
    await page.getByRole('button', { name: 'Sitzung starten' }).click();
    await expect(page.getByRole('heading', { name: 'Was möchtest du tun?' })).toBeVisible();
    await page.getByRole('button', { name: /Kontostand/ }).click();
    await expect(page.getByRole('heading', { name: 'Dein Kontostand' })).toBeVisible();
    await page.getByRole('button', { name: 'Hauptmenü' }).click();
    await page.getByRole('button', { name: /Geld einzahlen/ }).click();
    await expect(page.getByLabel('Betrag in Euro', { exact: true })).toHaveAttribute('readonly', '');
    await expect(page.getByRole('group', { name: 'Einzahlungsbetrag eingeben' })).toBeVisible();
    await page.getByRole('button', { name: 'Hauptmenü' }).click();
    await page.getByRole('button', { name: /Karte zurück/ }).click();

    await page.getByRole('button', { name: 'Touchscreen-Darstellung' }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'touch');
    await page.getByRole('radio', { name: /DEMO-002/ }).click();
    for (const digit of ['0', '0', '4', '2']) {
        await page.getByRole('button', { name: `Ziffer ${digit}` }).click();
    }
    await page.getByRole('button', { name: 'Sitzung starten' }).click();
    await page.getByRole('button', { name: /Umsätze/ }).click();
    await expect(page.getByRole('heading', { name: 'Buchungshistorie' })).toBeVisible();
    await page.getByRole('button', { name: 'Hauptmenü' }).click();
    await page.getByRole('button', { name: /Karte zurück/ }).click();

    await page.setViewportSize({ width: 375, height: 812 });
    for (const themeName of ['Klassik-ATM-Darstellung', 'Touchscreen-Darstellung']) {
        await page.getByRole('button', { name: themeName }).click();
        const widths = await page.evaluate(() => ({ viewport: window.innerWidth, document: document.documentElement.scrollWidth }));
        expect(widths.document).toBe(widths.viewport);
    }
});

test('operates the admin dashboard, tables and edit surfaces', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('E-Mail-Adresse').fill('operator@example.test');
    await page.getByLabel('Passwort').fill('local-demo-operator');
    await page.getByRole('button', { name: 'Sicher anmelden' }).click();
    await expect(page).toHaveURL('/admin');
    await expect(page.getByRole('heading', { name: /Guten Tag/ })).toBeVisible();
    await expect(page.getByRole('region', { name: 'Bargeldkassetten' })).toBeVisible();

    await page.getByRole('button', { name: 'Automat verwalten' }).click();
    await expect(page.getByRole('dialog', { name: 'Betriebsstatus ändern' })).toBeVisible();
    await page.getByRole('button', { name: 'Dialog schließen' }).click();

    await page.getByRole('button', { name: 'Bestand bearbeiten' }).first().click();
    await expect(page.getByRole('dialog', { name: /Kassette/ })).toBeVisible();
    await page.getByRole('button', { name: 'Seitenleiste schließen' }).click();

    await page.getByRole('button', { name: 'Konten & Karten' }).first().click();
    await page.getByPlaceholder('Name, Konto oder Karte suchen …').fill('Sam');
    await expect(page.getByRole('row', { name: /Sam Beispiel/ })).toBeVisible();
    await expect(page.getByRole('row', { name: /Alex Demo/ })).toBeHidden();
    await page.getByPlaceholder('Name, Konto oder Karte suchen …').fill('');

    await page.getByRole('button', { name: 'Neues Konto' }).click();
    const accountDialog = page.getByRole('dialog', { name: 'Neues Konto anlegen' });
    await accountDialog.getByLabel('Name der neuen Person').fill('Browser Admin');
    await accountDialog.getByLabel('Kontoreferenz').fill('BROWSER-003');
    await accountDialog.getByLabel('Erste Kartenreferenz').fill('BROWSER-CARD-003');
    await accountDialog.getByLabel('Vierstellige PIN').fill('1357');
    await accountDialog.getByRole('button', { name: 'Konto anlegen' }).click();
    await expect(page.getByRole('status')).toContainText('Konto und erste Karte wurden angelegt.');
    await expect(page.getByRole('row', { name: /Browser Admin/ })).toBeVisible();

    await page.getByRole('button', { name: 'BROWSER-003 verwalten' }).click();
    await page.getByRole('button', { name: 'Konto sperren' }).click();
    await expect(page.getByRole('row', { name: /BROWSER-003.*Gesperrt/ })).toBeVisible();
    await page.getByRole('button', { name: 'BROWSER-003 verwalten' }).click();
    await page.getByRole('button', { name: 'Konto reaktivieren' }).click();

    await page.getByRole('button', { name: 'Neue Karte' }).click();
    const cardDialog = page.getByRole('dialog', { name: 'Neue Karte anlegen' });
    await cardDialog.getByLabel('Konto').selectOption({ label: 'BROWSER-003 · Browser Admin' });
    await cardDialog.getByLabel('Kartenreferenz').fill('BROWSER-CARD-004');
    await cardDialog.getByLabel('Vierstellige PIN').fill('2468');
    await cardDialog.getByRole('button', { name: 'Karte anlegen' }).click();
    await expect(page.getByRole('status')).toContainText('Neue Karte wurde angelegt.');
    await page.getByRole('button', { name: 'BROWSER-003 verwalten' }).click();
    await expect(page.getByRole('dialog', { name: 'BROWSER-003' })).toContainText('BROWSER-CARD-004');
    await page.getByRole('button', { name: 'Seitenleiste schließen' }).click();

    await page.getByRole('button', { name: 'Transaktionen' }).first().click();
    const typeFilter = page.getByRole('combobox');
    await typeFilter.selectOption('withdrawal');
    await expect(typeFilter).toHaveValue('withdrawal');

    await page.setViewportSize({ width: 375, height: 812 });
    await page.reload();
    await expect(page.getByRole('navigation', { name: 'Mobile Admin-Navigation' })).toBeVisible();
    const widths = await page.evaluate(() => ({ viewport: window.innerWidth, document: document.documentElement.scrollWidth }));
    expect(widths.document).toBe(widths.viewport);
});
