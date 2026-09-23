# LERN-Bank Mein Geldautomat — v1.5

Laravel-/PHP-Lernprojekt mit einer Geldautomaten-Oberfläche. **Simulation ohne echte Bankanbindung.**

## Was funktioniert?

- Responsive deutsche Landingpage und gemeinsame App-Shell.
- Auswahl zweier Demo-Karten und Anmeldung mit vierstelliger PIN über Tastatur oder klickbares Nummernfeld.
- Persönliche Kontoübersicht mit Karte, Kontoreferenz und aktuellem Guthaben.
- Simulierte Ein- und Auszahlungen und eine kontobezogene Buchungshistorie mit zehn Einträgen pro Seite.
- Der Demo-Automat „LERN-Bank Mein Geldautomat“ mit bestandsgeführtem Bargeld und nachvollziehbarer Scheinverteilung.
- Stabile Belegreferenzen, kontogebundene Belegansicht und druckfreundliche Darstellung.
- Optionaler Verwendungszweck sowie nach Typ filter- und nach Datum oder Betrag sortierbare Historie.
- PIN-Sperre, Anfragelimit, Ablauf nach Inaktivität und Abmeldung.
- Getrennter Admin-Zugang mit Dashboard, Kennzahlen, Aktivitätsdiagramm und Tabellen für Konten, Karten, Transaktionen, Bargeldbestand und Audit-Ereignisse.
- Automatenstatus wird in einem Dialog, der Bargeldbestand in einer seitlichen Bearbeitungsleiste geändert; beide Aktionen bleiben serverseitig validiert und protokolliert.
- Admins können Demo-Konten samt erster Karte anlegen, zusätzliche Karten ausgeben, Konten oder einzelne Karten sperren und reaktivieren sowie PIN-Fehlversuche und temporäre Kartensperren zurücksetzen.
- Alle Admin-Tabellen verwenden PrimeVue DataTable mit eigenem Scrollbereich, fester Kopfzeile, Pagination, Mehrspalten-Sortierung, Suche und fachlichen Filtern. Der Tabellenzustand bleibt während der Browser-Sitzung erhalten.
- Der öffentliche Admin-Showcase besitzt einen Ein-Klick-Gastzugang. Gäste sehen Dashboard, Tabellen, Filter und Audit schreibgeschützt; Superadmins behalten exklusiv alle Änderungsfunktionen.
- Konto- und Kartensperren invalidieren laufende Sitzungen. Gesperrte oder abgelaufene Zugänge erscheinen nicht in der öffentlichen Kartenauswahl; PINs werden ausschließlich gehasht gespeichert.
- Datensparsames, unveränderliches Audit für Anmeldungen, Sitzungsabläufe, Geldbewegungen und Betreiberänderungen.
- Unter PostgreSQL geprüfte Sperren für konkurrierende Auszahlungen und idempotente Wiederholungen.
- Automatisierter Chrome-Hauptablauf mit isolierter Browser-Testdatenbank.
- Sicherheitsheader und verständliche Fehlerseiten bei deaktiviertem Debug-Modus.
- Fünf dauerhaft gespeicherte Darstellungen: hell, dunkel, Retro sowie ein klassischer Geldautomat und eine großflächige Touchscreen-Oberfläche.
- Klassik- und Touch-Modus führen nach der Anmeldung über klickbare Menüs zu Kontostand, Ein-/Auszahlung und Umsätzen; PIN und Geldbeträge lassen sich vollständig über Nummernfelder eingeben.
- Toast-Benachrichtigungen für erfolgreiche Aktionen und verzögertes Lade-Overlay bei längeren Seitenwechseln.
- Direkte Links zum Projekt auf GitHub und zum Portfolio; `PORTFOLIO_URL` setzt die Portfolio-Adresse zur Laufzeit.
- Explizit aktivierbarer öffentlicher Demo-Modus mit sichtbaren Demo-PINs, Reset und ungültig werdenden alten Sitzungen.
- GitHub-CI für SQLite, PostgreSQL, Browser und Produktionscontainer; Docker-Deployment mit PHP 8.4.
- Customer, Account, Card, Transaction, ATM und CashInventory als einfache Eloquent-Modelle mit Migrationen und lokalem Demo-Seeding.

Die öffentliche Demo läuft unter **https://atm.tiny-bits.org** auf Render Free mit Neon PostgreSQL; die technische Render-Adresse bleibt als Rückfalladresse erhalten. HTTPS, Sicherheitsheader, Proxyverhalten, Kaltstart, Speicherbedarf und der vollständige ATM-Ablauf wurden am Zielhost geprüft. Tageslimits und Scheinannahme bleiben außerhalb des Umfangs. Einzahlungen sind reine Kontobuchungen und erhöhen den Bargeldbestand nicht. Neue Demo-Konten starten bei 0 Cent.

## Lokal starten

```sh
git clone https://github.com/WolfgangSiegert/laravel-atm-showcase.git
cd laravel-atm-showcase
```

Führe anschließend die Schritte unter [Neue Installation](#neue-installation) aus und öffne **http://127.0.0.1:8000**. `composer dev` startet Laravel und Vite; Strg+C beendet beide.

## Demo-Zugangsdaten

| Karte | PIN | Fiktiver Kunde |
| --- | --- | --- |
| DEMO-001 | `1234` | Alex Demo |
| DEMO-002 | `0042` | Sam Beispiel |

Diese PINs sind absichtlich öffentlich bekannte Lernzugänge. Niemals persönliche PINs verwenden. Die Datenbank speichert nur Hashes. Die Kartenauswahl liefert keine PINs, Hashes, Kundennamen oder Kontodaten aus.

Nach fünf falschen PINs wird die Karte 15 Minuten gesperrt; danach kann wieder versucht werden. Zusätzlich höchstens zehn Anmeldeversuche je IP in einer Minute. Die Sitzung läuft nach fünf Minuten ohne serverseitige Aktivität ab. Eine Mausbewegung verlängert sie nicht. Werte stehen in `config/atm.php`. Diese Regeln sind vorläufige Lernprojekt-Entscheidungen, keine Sicherheitszusage für Banking.

Der lokale Superadmin-Zugang liegt unter `/admin`: `operator@example.test` mit Passwort `local-demo-operator`. Beide Werte sind über `DEMO_OPERATOR_EMAIL` und `DEMO_OPERATOR_PASSWORD` änderbar. Dieser bekannte Zugang wird ausschließlich in `local` und `testing` angelegt. In der öffentlichen Demo öffnet `PUBLIC_ADMIN_GUEST_ENABLED=true` einen passwortlosen, serverseitig schreibgeschützten Showcase-Gastzugang; ein privater Produktions-Superadmin wird weiterhin separat mit `php artisan atm:operator-create` angelegt. Die bisherigen `/operator`-Routen bleiben vorerst kompatibel.

## Geldbewegungen

Nach der PIN-Anmeldung einen Eurobetrag eingeben, zum Beispiel `25,50`. Komma oder Punkt als Dezimaltrennzeichen sind erlaubt, höchstens zwei Nachkommastellen; keine Tausendertrennzeichen. Bereich: 0,01 € bis 10.000,00 € pro Buchung. Das gesamte Demo-Guthaben ist auf 10.000.000,00 € begrenzt. Die Grenzen stehen in `config/atm.php`.

Ein optionaler Verwendungszweck mit höchstens 140 Zeichen kann bei Ein- und Auszahlung ergänzt werden. Außenliegende und mehrfache Leerzeichen werden normalisiert; Steuerzeichen werden abgewiesen. Der Text wird unveränderlich mit der Buchung gespeichert und erscheint in Historie und Beleg. Bei einem wiederholten Request zählt ein geänderter Verwendungszweck als Konflikt.

Saldo und Buchung werden atomar gespeichert. Ein Anfrageschlüssel verhindert Doppelbuchungen bei Wiederholungen; derselbe Schlüssel mit einem anderen Betrag wird abgewiesen. Es gibt keine Bearbeitungs- oder Löschfunktion für Buchungen. Der Anwendungsschutz ersetzt keine Datenbank-Revisionssicherheit.

Auszahlungen sind in ganzen Euro zwischen 10,00 € und 1.000,00 € möglich. Der Automat gibt 10-, 20-, 50- und 100-Euro-Scheine aus. Eine Buchung gelingt nur, wenn Guthaben und eine exakte Kombination aus dem aktuellen Scheinbestand ausreichen. Saldo, Bargeldbestand und Buchung werden gemeinsam gespeichert; bei einem Fehler bleibt alles unverändert. Der Wiederholungsschutz entspricht dem der Einzahlung.

Bei früheren Browserprüfungen wurde DEMO-002 zunächst um **25,50 €** erhöht und später um **20,00 €** belastet. In v0.6 folgten eine Einzahlung über **4,50 €** und eine Auszahlung über **10,00 €**, jeweils mit Verwendungszweck. Der lokale Saldo beträgt deshalb jetzt **0,00 €**. Diese nachvollziehbaren Testbuchungen bleiben bestehen; neue Installationen erhalten sie nicht automatisch. Fortlaufende Datenbank-IDs können durch zurückgerollte Vorgänge Lücken enthalten und sind keine lückenlosen Belegnummern.

Seit v0.5 erhält jede Buchung eine eindeutige, stabile `ATM-…`-Belegreferenz. Nach einer Geldbewegung folgt eine Abschlussansicht; ältere Belege lassen sich aus der Historie öffnen. Konto und Karte erscheinen dort maskiert. Die Druckfunktion verwendet den Browser-Druckdialog. Bei der Browserprüfung von v0.5 wurde DEMO-001 in drei Vorgängen insgesamt um **1,02 €** erhöht; sein lokaler Saldo beträgt nun **26,52 €**.

Für ein vorhandenes v0.2-Projekt: `php artisan migrate` und `npm run build`. Die Migration übernimmt einen eventuell bereits vorhandenen positiven Saldo als Anfangsbestandsbuchung, ohne den Saldo zu verändern. Im Normalfall waren die v0.2-Demo-Salden 0.

## Neue Installation

Voraussetzungen: PHP **8.4.x**, Composer 2, Node **24.x**, npm, PDO/SQLite und PDO/PostgreSQL sowie die üblichen Laravel-PHP-Erweiterungen. `.php-version` dokumentiert die PHP-Version; `.nvmrc` wird von `nvm use` ausgewertet. Beide Lockfiles reproduzieren die Paketversionen.

```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan db:seed
npm ci
npm run build
composer dev
```

Eine bestehende `.env` nicht überschreiben. Alternativ `composer setup` für die erstmalige Einrichtung und danach `php artisan db:seed`. `composer setup` erzeugt bei erneutem Aufruf einen neuen Anwendungsschlüssel; für spätere Updates die einzelnen Befehle verwenden.

Normales vollständiges Demo-Seeding ist nur in `local` und `testing` erlaubt. Wiederholtes Seeding verändert bestehende PINs, Sperren und Salden nicht. Produktion verwendet ausschließlich `atm:demo-provision` bei ausdrücklich aktiviertem `PUBLIC_DEMO_ENABLED=true`; der bekannte Betreiber wird dort niemals angelegt. Ohne Seeding zeigt die Kartenauswahl einen erklärenden Leerzustand. Die SQLite-Datei liegt in `database/database.sqlite`; Tests erzwingen eine separate `:memory:`-Datenbank.

Der Betreiberbereich kann den konfigurierten Automaten aktiv beziehungsweise außer Betrieb setzen und die Anzahl einer Stückelung um höchstens 100 Scheine je Vorgang ändern. Negative Bestände werden atomar abgewiesen. Audit-Ereignisse enthalten technische Zuordnungen, Ergebnis, Grundcode und eine kleine strukturierte Kontextmenge; PIN, Passwort, IP-Adresse und Verwendungszweck werden nicht ins Audit kopiert. Im öffentlichen Demo-Modus werden Konto-Audits beim Reset und weitere Audits nach sieben Tagen verworfen. Lokal bleibt die Historie erhalten.

## Prüfungen

```sh
composer test
vendor/bin/pint --test
npm run build
npm run test:browser
composer validate --strict
composer check-platform-reqs
```

Der Build enthält die strikte TypeScript-Prüfung. Die Testbasis prüft zusätzlich zu HTTP/Inertia/Migrationen PIN-Schutz und Sitzungen sowie Cent-Genauigkeit, Betragsgrenzen, Doppelanfragen, Verwendungszwecke, Filter, Sortierung, Kontozuordnung, Rollback, Historienseiten, Anfangsbestände und begrenzte Scheinkombinationen. Details und Grenzen stehen im [Prüfprotokoll](docs/verification.md).

Die PostgreSQL-Tests benötigen eine ausschließlich für Tests bestimmte leere Datenbank, deren Name auf `_test` endet. Sie führen `migrate:fresh` aus und löschen deshalb sämtliche Tabellen in dieser Datenbank:

```sh
ATM_POSTGRES_TEST_URL='postgresql://user:password@127.0.0.1:5432/atm_test' composer test:postgres
```

Ohne `ATM_POSTGRES_TEST_URL` werden diese fünf Tests im normalen SQLite-Lauf übersprungen. Niemals eine Entwicklungs- oder Produktionsdatenbank als Test-URL verwenden.

`npm run test:browser` verwendet den lokal installierten Google Chrome, startet Laravel auf Port 8010 und legt vorübergehend `database/browser-testing.sqlite` an. Der Ablauf prüft falsche und richtige PIN, Ein- und Auszahlung, beide Belege, Kontostand, Abmeldung, Tastatureingabe und die mobile Breite. Die Datei wird danach gelöscht; die normale lokale Datenbank bleibt unverändert.

In CI wird Chromium über Playwright installiert und mit `PLAYWRIGHT_BROWSER=chromium` gewählt. PHP 8.4 und Node 24 müssen auch beim lokalen Browserlauf im aktiven Suchpfad stehen.

## Öffentliche Demo und Deployment

Der öffentliche Modus wird ausschließlich für eine dedizierte fiktive Datenbank mit `PUBLIC_DEMO_ENABLED=true` aktiviert. Besucher sehen die veröffentlichten PINs direkt auf der Anmeldeseite und einen Hinweis auf gemeinsam genutzte Konten. Nach 24 Stunden greift bei der nächsten ATM-Anfrage ein atomarer Reset: Demo-Salden auf 0 €, alte Demo-Buchungen entfernen, Kartensperren aufheben, Scheine auffüllen und alte Kartensitzungen ungültig machen. Lokal ist dieser Modus standardmäßig deaktiviert. Ein manueller Reset benötigt zusätzlich `php artisan atm:demo-reset --force` und löscht fiktive Daten.

[Deployment-Anleitung](docs/deployment.md) beschreibt Render Free/Neon Free, Secrets, sicheren Betreiberzugang, Abnahme und Wiederherstellung. Lizenz: [MIT](LICENSE).

## Struktur und Routing

| Pfad | Aufgabe |
| --- | --- |
| `app/Models/` | Fachmodelle, technischer Betreiber-User und AuditEvent |
| `app/Http/Controllers/AtmSessionController.php` | Kartenauswahl, PIN-Prüfung und Sitzungsantworten |
| `app/Http/Controllers/ReceiptController.php` | Kontogebundene, maskierte Belegantwort |
| `app/Actions/DepositMoney.php` | Atomare Kontobuchung und Wiederholungsschutz |
| `app/Actions/WithdrawMoney.php` | Atomare Belastung, Scheinentnahme und Wiederholungsschutz |
| `app/Support/CashCombination.php` | Findet eine mögliche Kombination aus dem begrenzten Scheinbestand |
| `app/Http/Requests/DepositRequest.php` | Betragsvalidierung und Umrechnung in Cent |
| `app/Http/Middleware/RequireAtmSession.php` | Gültigkeit und Inaktivitätsgrenze auf geschützten Routen |
| `app/Http/Middleware/AddSecurityHeaders.php` | Sicherheitsheader und produktionsabhängige CSP/HSTS-Antworten |
| `app/Http/Controllers/OperatorDashboardController.php` | Geschützte Dashboard-Daten, Status- und Bestandsverwaltung |
| `app/Support/AuditLogger.php` | Zentral begrenzte Erzeugung von Audit-Ereignissen |
| `resources/js/pages/Atm/` | Welcome, SignIn und Session mit Kontoübersicht |
| `resources/js/pages/Operator/` | Admin-Anmeldung und Material-inspiriertes Dashboard |
| `resources/js/layouts/AdminShell.vue` | Eigenständige Admin-Navigation für Desktop und Mobilgeräte |
| `resources/js/layouts/AppShell.vue` | Gemeinsamer Rahmen und Statusmeldungen |
| `database/migrations/` | Laravel-Infrastruktur sowie Identitätstabellen und Buchungen |
| `tests/Feature/` | Pest-Integrationstests |
| `tests/Browser/` | Isolierter Playwright-Hauptablauf |
| `docs/` | Fachmodell, Entscheidungen, Prüfprotokoll |

| Methode | Route | Verhalten |
| --- | --- | --- |
| GET/HEAD | `/` | Weiterleitung auf `/atm` |
| GET/HEAD | `/atm` | Landingpage |
| GET/HEAD | `/atm/cards` | Öffentliche Demo-Kartenauswahl |
| POST | `/atm/session` | PIN prüfen und Sitzung starten |
| GET/HEAD | `/atm/session` | Geschützte Sitzungsseite |
| GET/HEAD | `/atm/receipts/{reference}` | Geschützter Beleg des Sitzungskontos |
| POST | `/atm/deposits` | Geschützte simulierte Einzahlung |
| POST | `/atm/withdrawals` | Geschützte simulierte Auszahlung |
| DELETE | `/atm/session` | Sitzung und CSRF-Token erneuern, abmelden |
| GET/HEAD | `/admin/login` | Admin-Anmeldung |
| GET/HEAD | `/admin` | Geschütztes Dashboard mit Verwaltungsansichten |
| PATCH | `/admin/atm/status` | Geschützte Statusänderung |
| POST | `/admin/inventory/{id}/adjust` | Geschützte Bestandsänderung |
| POST | `/admin/accounts` | Demo-Konto mit erster Karte anlegen |
| PATCH | `/admin/accounts/{id}/status` | Konto sperren oder reaktivieren |
| POST | `/admin/accounts/{id}/cards` | Zusätzliche Karte ausgeben |
| PATCH | `/admin/cards/{id}/status` | Karte sperren oder reaktivieren |
| POST | `/admin/cards/{id}/reset-lock` | PIN-Fehlversuche und Zeitsperre zurücksetzen |
| DELETE | `/admin/session` | Admin-Sitzung beenden |
| GET/HEAD | `/up` | Laravel-Healthcheck |

Konventionelles Laravel mit Vue 3, TypeScript, Inertia 3, Vite und Tailwind. Kein zusätzlicher Client-Router, kein SSR, keine Repository-/DDD-Schichten. ATM-Kartensitzungen bleiben unabhängig vom Laravel-User des Betreiberbereichs. Das öffentliche GitHub-Repository und das Render-Deployment sind eingerichtet.

## Weiterentwicklung

Der v1.0-Umfang ist abgeschlossen. Mögliche spätere Erweiterungen und bewusst gesetzte Grenzen stehen in [Roadmap](docs/roadmap.md), [Deployment](docs/deployment.md), [Hosting-Empfehlung](docs/hosting.md) sowie [Entscheidungen](docs/decisions.md).

Offizielle Referenzen: [Laravel 13](https://laravel.com/framework/docs/releases), [Inertia-Setup](https://inertiajs.com/docs/v3/installation/server-side-setup), [Laravel Rate Limiting](https://github.com/laravel/docs/blob/13.x/rate-limiting.md), [Inertia History Encryption](https://inertiajs.com/docs/v3/security/history-encryption).
