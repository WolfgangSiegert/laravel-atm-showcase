# Cash Machine — v0.5

Laravel-/PHP-Lernprojekt mit einer Geldautomaten-Oberfläche. **Simulation ohne echte Bankanbindung.**

## Was funktioniert?

- Responsive deutsche Landingpage und gemeinsame App-Shell.
- Auswahl zweier Demo-Karten und Anmeldung mit vierstelliger PIN.
- Persönliche Kontoübersicht mit Karte, Kontoreferenz und aktuellem Guthaben.
- Simulierte Ein- und Auszahlungen und eine kontobezogene Buchungshistorie mit zehn Einträgen pro Seite.
- Ein Demo-Automat mit bestandsgeführtem Bargeld und nachvollziehbarer Scheinverteilung.
- Stabile Belegreferenzen, kontogebundene Belegansicht und druckfreundliche Darstellung.
- PIN-Sperre, Anfragelimit, Ablauf nach Inaktivität und Abmeldung.
- Customer, Account, Card, Transaction, ATM und CashInventory als einfache Eloquent-Modelle mit Migrationen und lokalem Demo-Seeding.

**Noch nicht implementiert:** Tageslimits, Scheinannahme, Audit fehlgeschlagener Versuche und geschützte Automatenverwaltung. Einzahlungen sind weiterhin reine Kontobuchungen und erhöhen den Bargeldbestand nicht. Neue Demo-Konten starten bei 0 Cent.

## Auf diesem Mac starten

```sh
cd /Users/Wolfgang/developer/projects/LaravelCashMashine
export PATH="/opt/homebrew/opt/php@8.4/bin:$PATH"
nvm use
composer dev
```

Falls nvm nicht geladen ist, statt `nvm use`:

```sh
export PATH="/Users/Wolfgang/.nvm/versions/node/v24.20.0/bin:$PATH"
```

Öffne **http://127.0.0.1:8000**. `composer dev` startet Laravel und Vite; Strg+C beendet beide. Auf diesem Mac wurden Abhängigkeiten, Schlüssel, SQLite-Datei, Migrationen, Demo-Daten und Build bereits eingerichtet. Die globale PHP-/Node-Auswahl wurde nicht verändert.

## Demo-Zugangsdaten

| Karte | PIN | Fiktiver Kunde |
| --- | --- | --- |
| DEMO-001 | `1234` | Alex Demo |
| DEMO-002 | `0042` | Sam Beispiel |

Diese PINs sind absichtlich öffentlich bekannte Lernzugänge. Niemals persönliche PINs verwenden. Die Datenbank speichert nur Hashes. Die Kartenauswahl liefert keine PINs, Hashes, Kundennamen oder Kontodaten aus.

Nach fünf falschen PINs wird die Karte 15 Minuten gesperrt; danach kann wieder versucht werden. Zusätzlich höchstens zehn Anmeldeversuche je IP in einer Minute. Die Sitzung läuft nach fünf Minuten ohne serverseitige Aktivität ab. Eine Mausbewegung verlängert sie nicht. Werte stehen in `config/atm.php`. Diese Regeln sind vorläufige Lernprojekt-Entscheidungen, keine Sicherheitszusage für Banking.

## Geldbewegungen in v0.4

Nach der PIN-Anmeldung einen Eurobetrag eingeben, zum Beispiel `25,50`. Komma oder Punkt als Dezimaltrennzeichen sind erlaubt, höchstens zwei Nachkommastellen; keine Tausendertrennzeichen. Bereich: 0,01 € bis 10.000,00 € pro Buchung. Das gesamte Demo-Guthaben ist auf 10.000.000,00 € begrenzt. Die Grenzen stehen in `config/atm.php`.

Saldo und Buchung werden atomar gespeichert. Ein Anfrageschlüssel verhindert Doppelbuchungen bei Wiederholungen; derselbe Schlüssel mit einem anderen Betrag wird abgewiesen. Es gibt keine Bearbeitungs- oder Löschfunktion für Buchungen. Der Anwendungsschutz ersetzt keine Datenbank-Revisionssicherheit.

Auszahlungen sind in ganzen Euro zwischen 10,00 € und 1.000,00 € möglich. Der Automat gibt 10-, 20-, 50- und 100-Euro-Scheine aus. Eine Buchung gelingt nur, wenn Guthaben und eine exakte Kombination aus dem aktuellen Scheinbestand ausreichen. Saldo, Bargeldbestand und Buchung werden gemeinsam gespeichert; bei einem Fehler bleibt alles unverändert. Der Wiederholungsschutz entspricht dem der Einzahlung.

Bei der Browserprüfung wurde DEMO-002 zunächst um **25,50 €** erhöht und später um **20,00 €** belastet. Der lokale Saldo beträgt deshalb **5,50 €**; beide nachvollziehbaren Testbuchungen bleiben bestehen. Neue Installationen erhalten sie nicht automatisch. Fortlaufende Datenbank-IDs können durch zurückgerollte Vorgänge Lücken enthalten und sind keine lückenlosen Belegnummern.

Seit v0.5 erhält jede Buchung eine eindeutige, stabile `ATM-…`-Belegreferenz. Nach einer Geldbewegung folgt eine Abschlussansicht; ältere Belege lassen sich aus der Historie öffnen. Konto und Karte erscheinen dort maskiert. Die Druckfunktion verwendet den Browser-Druckdialog. Bei der Browserprüfung von v0.5 wurde DEMO-001 in drei Vorgängen insgesamt um **1,02 €** erhöht; sein lokaler Saldo beträgt nun **26,52 €**.

Für ein vorhandenes v0.2-Projekt: `php artisan migrate` und `npm run build`. Die Migration übernimmt einen eventuell bereits vorhandenen positiven Saldo als Anfangsbestandsbuchung, ohne den Saldo zu verändern. Im Normalfall waren die v0.2-Demo-Salden 0.

## Neue Installation

Voraussetzungen: PHP **8.4.x**, Composer 2, Node **24.x**, npm und PDO/SQLite sowie die üblichen Laravel-PHP-Erweiterungen. `.php-version` dokumentiert die PHP-Version; `.nvmrc` wird von `nvm use` ausgewertet. Beide Lockfiles reproduzieren die Paketversionen.

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

Demo-Seeding ist nur in `local` und `testing` erlaubt. Wiederholtes Seeding verändert bestehende PINs, Sperren und Salden nicht. Ohne Seeding zeigt die Kartenauswahl einen erklärenden Leerzustand. Die SQLite-Datei liegt in `database/database.sqlite`; Tests erzwingen eine separate `:memory:`-Datenbank.

## Prüfungen

```sh
composer test
vendor/bin/pint --test
npm run build
composer validate --strict
composer check-platform-reqs
```

Der Build enthält die strikte TypeScript-Prüfung. Die Testbasis prüft zusätzlich zu HTTP/Inertia/Migrationen PIN-Schutz und Sitzungen sowie Cent-Genauigkeit, Betragsgrenzen, Doppelanfragen, Kontozuordnung, Rollback, Historienseiten, Anfangsbestände und begrenzte Scheinkombinationen. Details und Grenzen stehen im [Prüfprotokoll](docs/verification.md).

## Struktur und Routing

| Pfad | Aufgabe |
| --- | --- |
| `app/Models/` | Customer, Account, Card, Transaction, Atm, CashInventory und technischer Laravel-User |
| `app/Http/Controllers/AtmSessionController.php` | Kartenauswahl, PIN-Prüfung und Sitzungsantworten |
| `app/Http/Controllers/ReceiptController.php` | Kontogebundene, maskierte Belegantwort |
| `app/Actions/DepositMoney.php` | Atomare Kontobuchung und Wiederholungsschutz |
| `app/Actions/WithdrawMoney.php` | Atomare Belastung, Scheinentnahme und Wiederholungsschutz |
| `app/Support/CashCombination.php` | Findet eine mögliche Kombination aus dem begrenzten Scheinbestand |
| `app/Http/Requests/DepositRequest.php` | Betragsvalidierung und Umrechnung in Cent |
| `app/Http/Middleware/RequireAtmSession.php` | Gültigkeit und Inaktivitätsgrenze auf geschützten Routen |
| `resources/js/pages/Atm/` | Welcome, SignIn und Session mit Kontoübersicht |
| `resources/js/layouts/AppShell.vue` | Gemeinsamer Rahmen und Statusmeldungen |
| `database/migrations/` | Laravel-Infrastruktur sowie Identitätstabellen und Buchungen |
| `tests/Feature/` | Pest-Integrationstests |
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
| GET/HEAD | `/up` | Laravel-Healthcheck |

Konventionelles Laravel mit Vue 3, TypeScript, Inertia 3, Vite und Tailwind. Kein zusätzlicher Client-Router, kein SSR, keine Repository-/DDD-Schichten. ATM-Sitzungen sind unabhängig vom technischen Laravel-User. Vor späterer Veröffentlichung sind HTTPS, Ziel-Datenbank und Betriebsregeln gesondert festzulegen; kein Deployment oder Remote-Repository angelegt.

## Weiterentwicklung

Als Nächstes folgt v0.6 mit optionalem Verwendungszweck sowie filter- und sortierbarer Transaktionsübersicht. Wegen der geplanten öffentlichen Showcase-Instanz sind PostgreSQL, ein authentifizierter Betreiberbereich, HTTPS, Demo-Reset, CI und Produktionshärtung Teil des verbindlichen Wegs zu v1.0. Siehe [Roadmap](docs/roadmap.md), [Hosting-Empfehlung](docs/hosting.md) sowie [Entscheidungen](docs/decisions.md).

Offizielle Referenzen: [Laravel 13](https://laravel.com/framework/docs/releases), [Inertia-Setup](https://inertiajs.com/docs/v3/installation/server-side-setup), [Laravel Rate Limiting](https://github.com/laravel/docs/blob/13.x/rate-limiting.md), [Inertia History Encryption](https://inertiajs.com/docs/v3/security/history-encryption).
