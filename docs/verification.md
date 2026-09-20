# Prüfprotokoll — v1.0-rc.1

Lokale Prüfung am 14. September 2026 mit PHP 8.4.25 und Node 24.20.0.

- **117 Pest-Tests, 745 Assertions erfolgreich; fünf PostgreSQL-Szenarien im SQLite-Lauf übersprungen.**
- **Zwei Chrome-Browserabläufe erfolgreich**, jetzt mit aktiviertem öffentlichen Demo-Modus und sichtbaren PINs.
- TypeScript/Produktionsbuild, Pint und Composer-Validierung erfolgreich. Plattformanforderungen mit ausdrücklich aktiviertem PHP 8.4 geprüft.
- Neue Migration auf der bestehenden lokalen Datenbank erfolgreich; vorhandene Salden/Buchungen nicht zurückgesetzt.

Reset-Prüfungen bestätigen ausdrückliches Opt-in und CLI-Bestätigung, erhaltene fremde Konten/Buchungen/Betreiber, Nullsaldo und wiederhergestellten Scheinbestand, ungültige alte Sitzungen und bereits vorbereitete Buchungsanfragen, einmaligen fälligen Reset, sieben Tage Audit-Aufbewahrung, öffentliche Buchungslimits und ausschließlich sichtbare Demo-PINs im öffentlichen Modus. Ein Proxytest bestätigt HTTPS-Forwarding nur von konfigurierten IPs.

Die [GitHub-CI für Commit fb6703c](https://github.com/WolfgangSiegert/laravel-atm-showcase/actions/runs/34834098957) ist vollständig grün: 117 Standardtests mit 745 Assertions, fünf PostgreSQL-Mehrprozessszenarien mit 20 Assertions und zwei Chromium-Browserabläufe. Der Containerjob baute das Image in 2 Minuten 36 Sekunden einschließlich Startprüfung erfolgreich und bestätigte HTTP-Antworten auf `/up` und `/atm/cards` sowie den noch nicht fälligen CLI-Reset gegen isoliertes PostgreSQL. Der Prüfjob lief 1 Minute 39 Sekunden. Diese Zeiten sind CI-Zeiten und keine gemessenen Render-Kaltstarts.

Der lokale Docker-Daemon ist nicht aktiv; Build und Runtime wurden deshalb auf dem Linux-Runner geprüft. Die zwei zusätzlichen PostgreSQL-Szenarien bestätigen Reset gegen vorbereitete Auszahlung und zwei gleichzeitig fällige Resets. Öffentliche Render-/Neon-Instanz, tatsächliches TLS/Proxyverhalten, Kaltstart und Free-Instanzspeicher sind noch nicht bestätigt; der Stand bleibt deshalb ein Release Candidate.

---

# Historisches Prüfprotokoll — v0.9

Abgeschlossen am 11. September 2026. Playwright wurde als Entwicklungsabhängigkeit ergänzt.

- **107 Pest-Tests, 678 Assertions erfolgreich; 3 PostgreSQL-Tests im SQLite-Standardlauf erwartungsgemäß übersprungen.**
- **2 Playwright-Tests erfolgreich** in lokalem Google Chrome.
- Produktionsbuild einschließlich strikter TypeScript-Prüfung erfolgreich mit Node 24.20.0.

Der automatisierte Browserlauf verwendet eine eigene SQLite-Datei und löscht sie anschließend. Er prüft falsche PIN und sichtbare Fehlermeldung, erfolgreiche Nummernfeld-Anmeldung, Einzahlung über 50,00 EUR, Auszahlung über 20,00 EUR, beide Belege, „LERN-Bank Mein Geldautomat“, Endsaldo 30,00 EUR und Abmeldung. Der zweite Test bestätigt Tastatureingabe, Rückschritt, vollständiges Löschen und fehlenden horizontalen Überlauf bei 375 × 812 Pixeln.

Der erste E2E-Lauf fand ein falsches Rücksprungziel nach fehlgeschlagener PIN aus einer Inertia-Navigation. Nach expliziten Fehlerzielen für Anmeldungen besteht der vollständige Ablauf. Die produktionsabhängige Fehlerseite sowie Basisheader, CSP und HSTS werden zusätzlich durch Featuretests geprüft.

Grenzen: Chrome ist der einzige automatisierte Browser. Screenreader und reale Mobilgeräte wurden nicht geprüft. Die CSP enthält für Styles weiterhin `'unsafe-inline'`; HSTS und sichere Cookies setzen eine korrekt erkannte HTTPS-Verbindung beim späteren Host voraus. Öffentliche Abuse-Grenzen, Demo-Reset und Zielhost-Konfiguration folgen in v1.0.

---

# Historisches Prüfprotokoll — v0.8

Abgeschlossen am 11. September 2026. Keine neuen PHP- oder JavaScript-Pakete; `ext-pdo_pgsql` ist nun explizite Plattformanforderung.

- **104 SQLite-Tests, 652 Assertions erfolgreich; 3 PostgreSQL-Tests im Standardlauf erwartungsgemäß übersprungen.**
- **3 PostgreSQL-Mehrprozesstests, 12 Assertions erfolgreich** unter PHP 8.4.25 und PostgreSQL 18.6.
- Produktionsbuild einschließlich strikter TypeScript-Prüfung erfolgreich mit Node 24.20.0.

Jedes PostgreSQL-Szenario führte zunächst alle Migrationen auf einer leeren Testdatenbank aus. Zwei getrennte PHP-Prozesse starteten danach gleichzeitig. Bestätigt sind genau eine Belastung bei unzureichendem gemeinsamem Kontoguthaben, genau eine Ausgabe des letzten passenden 100-Euro-Scheins an zwei konkurrierende Konten und genau eine Transaction bei zwei identischen Idempotenzschlüsseln. Salden, Scheinbestand und Buchungsanzahl blieben in allen Fällen konsistent.

Der sichtbare ATM-Name wurde per Migration und Seeder zu „LERN-Bank Mein Geldautomat“ geändert. Die PIN-Seite bietet zusätzlich zur Tastatureingabe ein klickbares Nummernfeld mit Rückschritt und vollständigem Löschen. Im Browser wurde DEMO-002 vollständig über das Nummernfeld einschließlich Rückschritt eingegeben und erfolgreich angemeldet. Die neue Bezeichnung erschien anschließend im Betreiberbereich. Bei 375 × 812 Pixeln blieb die Dokumentbreite exakt 375 Pixel, das Nummernfeld vollständig bedienbar und das Browserfehlerprotokoll leer.

Grenzen: Die PostgreSQL-Prüfung verwendet einen lokalen Einzelserver ohne Verbindungsproxy. Sie simuliert keine Prozessabbrüche, Netzfehler, hohe Dauerlast oder mehrere Webserver. Anbieterbedingungen von Neon/Render und ein mögliches späteres Pooling bleiben im Deployment-Prototyp zu prüfen.

---

# Historisches Prüfprotokoll — v0.7

Abgeschlossen am 10. September 2026. Keine neuen Paketabhängigkeiten.

- **104 Tests, 650 Assertions erfolgreich** unter PHP 8.4.25.
- Produktionsbuild einschließlich strikter TypeScript-Prüfung erfolgreich mit Node 24.20.0.
- Pint, strikte Composer-Validierung und Plattformanforderungen unter PHP 8.4.25 erfolgreich.

Die neuen Tests prüfen die Trennung von Betreiber und normalem User, Session-Rotation, Passwortschutz in Fehlerweiterleitungen, fehlgeschlagene Anmeldungen und Rate-Limit, geschützten Dashboardzugriff, Statuswechsel, atomare Bestandsänderung, Fremdautomaten, Abmeldung und Audit-Inhalte. Weitere Prüfungen sichern erfolgreiche und abgewiesene Kartenanmeldungen, Sitzungsablauf, Ein- und Auszahlungsaudit sowie den anwendungsseitigen Änderungsschutz der Audit-Ereignisse. Ein Test durchsucht die serialisierten Ereignisse ausdrücklich nach den verwendeten PINs, IP-Adresse und einem privaten Verwendungszweck.

Die lokale Migration und das lokale Demo-Seeding liefen erfolgreich; bestehende Kontosalden und Bargeldmengen wurden dabei nicht zurückgesetzt. Im integrierten Browser wurde der lokale Betreiber angemeldet. Der ATM wurde auf Wartung und zurück auf aktiv gesetzt, der Bestand eines 10-Euro-Scheins erhöht und wieder vermindert. Dashboard und Audit zeigten alle Änderungen; der fachliche Endzustand entspricht dem Zustand vor der Prüfung. Das Browserprotokoll blieb ohne JavaScript-Fehler.

Bei der ersten mobilen Prüfung hatte die Seite bei 375 Pixeln Breite eine Dokumentbreite von 551 Pixeln. Ursache war die Mindestbreite langer JSON-Auditzeilen innerhalb des CSS-Grids. Nach begrenzten Grid-Spalten und erzwungenem Textumbruch beträgt die Dokumentbreite exakt 375 Pixel; die Betreiberansicht bleibt bedienbar. Die temporäre Viewport-Vorgabe wurde anschließend zurückgesetzt.

Grenzen: Das Audit ist auf Anwendungsebene append-only und nicht extern revisionssicher. Eine Aufbewahrungsregel fehlt. Der bekannte lokale Betreiber wird in Produktion nicht angelegt; der Produktionsweg für das erste Betreiberkonto folgt mit dem Deployment. PostgreSQL, verteiltes Rate-Limiting, automatisierte Browserläufe und öffentliche Erreichbarkeit bleiben ungeprüft.

---

# Historisches Prüfprotokoll — v0.6

Abgeschlossen am 10. September 2026. Keine neuen Paketabhängigkeiten.

- **92 Tests, 564 Assertions erfolgreich** unter PHP 8.4.25.
- Produktionsbuild einschließlich strikter TypeScript-Prüfung erfolgreich.
- Pint erfolgreich; lokale Migration der optionalen `purpose`-Spalte ausgeführt.

Die neuen Tests prüfen Normalisierung, Länge und Steuerzeichen des Verwendungszwecks, Speicherung in beiden Buchungsarten, Anzeige auf Beleg und Historie sowie Konflikte bei Wiederverwendung eines Idempotenzschlüssels. Für die Übersicht werden Typfilter, Betrags- und Datumssortierung in beide Richtungen, deterministische Gleichstände, sichere Fallbacks und erhaltene Query-Parameter in Pagination-URLs geprüft.

Im Browser wurden auf DEMO-002 eine Einzahlung über 4,50 EUR und eine Auszahlung über 10,00 EUR mit unterschiedlichen Verwendungszwecken ausgeführt. Beide Texte erschienen in Beleg und Historie. Der Filter `deposit` blendete Auszahlungen aus; die Sortierung `amount desc` zeigte 25,50 EUR vor 4,50 EUR. Die URL enthielt die gewählten Filterwerte. Bei 375 Pixeln Breite entsprach die Dokumentbreite exakt der Viewportbreite; keine Browserwarnungen oder JavaScript-Fehler wurden protokolliert. Der lokale Saldo von DEMO-002 beträgt danach 0,00 EUR.

Grenzen: Es gibt noch keine freie Textsuche, Zeitraumfilterung oder Exportfunktion. Das ist für den vereinbarten v1.0-Umfang nicht erforderlich. Datenbankverhalten unter PostgreSQL und parallele Abfragen bleiben für v0.8 vorgesehen.

---

# Historisches Prüfprotokoll — v0.5

Abgeschlossen am 10. September 2026. Keine neuen Paketabhängigkeiten.

- **81 Tests, 447 Assertions erfolgreich** unter PHP 8.4.25.
- Produktionsbuild einschließlich TypeScript-Prüfung erfolgreich; eigener Chunk für die Belegseite.
- Migration ergänzt bestehende und neue Transactions um eine eindeutige, nicht leere Belegreferenz.

Die neuen Tests prüfen Weiterleitung auf den entstandenen Beleg, Referenzformat und Stabilität bei idempotenter Wiederholung, maskierte Konto- und Kartenreferenzen, Auszahlungsautomat und Scheinverteilung, Kontotrennung sowie den Schutz durch eine aktive ATM-Sitzung. Die Datenbankprüfung schließt die neue Spalte ein.

Im Browser wurde DEMO-001 angemeldet und zunächst eine Einzahlung von 1,00 EUR ausgeführt. Zwei weitere Buchungen über je 0,01 EUR dienten der Diagnose und Nachprüfung der Scrollposition. Nach der Korrektur öffnet der mobile Beleg am Seitenanfang und zeigt die Bestätigung vollständig. Die Belege enthalten jeweils eine stabile `ATM-…`-Referenz, maskiertes Konto und maskierte Karte; der lokale Saldo beträgt nun 26,52 EUR. Diese Testbuchungen bleiben erhalten. Der Browser-Druckdialog selbst wurde nicht automatisiert; Druck-CSS und Produktionsbuild wurden geprüft.

Grenzen: Der Beleg ist ausdrücklich ein Demo-Artefakt und erfüllt keine rechtlichen oder steuerlichen Nummernkreise. Die öffentliche Zielumgebung und der Hosting-Anbieter sind noch nicht gewählt. PostgreSQL, Betreiber-Authentifizierung, CI und Produktionshärtung folgen gemäß Roadmap.

---

# Historisches Prüfprotokoll — v0.4

Abgeschlossen am 10. September 2026. Keine neuen Paketabhängigkeiten.

- **76 Tests, 394 Assertions erfolgreich** unter PHP 8.4.25.
- Produktionsbuild einschließlich strikter TypeScript-Prüfung erfolgreich.
- Pint, strikte Composer-Validierung und Plattformprüfung erfolgreich.
- Neue lokale Migrationen sowie Demo-ATM-Seeding erfolgreich ausgeführt.

Die neuen Tests prüfen exakte Auszahlungen, Guthaben- und Betragsgrenzen, nicht darstellbare Beträge, erschöpften Bestand, eine begrenzte Nicht-Greedy-Kombination, atomaren Rollback, Wiederholungsschutz, manipulierte IDs, ungültige Sitzungen, inaktive Automaten, gespeicherte Scheinverteilungen und idempotentes Seeding.

Im Browser wurde DEMO-002 mit 25,50 EUR Guthaben angemeldet. Eine Auszahlung von 30,00 EUR wurde wegen fehlender Deckung abgewiesen; Saldo und Historie blieben unverändert. Anschließend wurde eine Auszahlung von 20,00 EUR erfolgreich als ein 20-Euro-Schein gebucht. Der lokale Saldo beträgt nun 5,50 EUR. Die Historie zeigt Einzahlung und Auszahlung samt Saldo danach und Scheinverteilung. Die sichtbare ID `#3` enthält eine Lücke und bestätigt, dass technische IDs nicht als lückenlose Belegnummern behandelt werden dürfen.

Die Sitzung wurde bei 375 × 812 Pixeln geprüft und blieb bedienbar. Die Browser-Schnittstelle stellte in diesem Lauf kein direktes Konsolenprotokoll bereit; deshalb kann für v0.4 keine neue, belastbare Aussage zur Abwesenheit von Browser-Konsolenwarnungen gemacht werden. Build und Interaktionen waren fehlerfrei. Die temporäre Browsergröße wurde zurückgesetzt.

Grenzen: kein paralleler Lasttest, keine PostgreSQL-Prüfung, kein vollständiges Accessibility-, Sicherheits- oder Cross-Browser-Audit. Keine physische Geldausgabe, Scheinannahme, Beleggenerierung oder Bankanbindung.

---

# Historisches Prüfprotokoll — v0.3

Abgeschlossen am 10. September 2026. Paketabhängigkeiten unverändert.

- **54 Tests, 283 Assertions erfolgreich** unter PHP 8.4.25.
- Produktionsbuild inklusive TypeScript-Prüfung erfolgreich.
- Pint und strikte Composer-Validierung erfolgreich.
- Migration für Buchungen lokal ausgeführt. Bestehende positive Salden werden ohne Saldoänderung als Anfangsbestand übernommen; dafür besteht ein eigener Test.

Neue Tests prüfen genaue Cent-Umrechnung, zulässige Dezimalformate, Betrags-/Guthabengrenzen, wiederholte Anfragen, unterschiedliche Beträge mit gleichem Schlüssel, erzwungene Kontozuordnung aus der Sitzung, fremde Historien, abgelaufene/ungültige Sitzungen, Rollback bei fehlgeschlagener Buchung, anwendungsseitigen Änderungsschutz und Pagination.

Im Browser wurde DEMO-002 angemeldet und genau eine Einzahlung von 25,50 EUR ausgeführt. Saldo zuvor 0,00 EUR, danach 25,50 EUR; eine entsprechende Buchung sichtbar. Nach Neuladen bleiben Saldo und Buchung erhalten. Die Buchung wird nicht gelöscht und bleibt als nachvollziehbarer Testeintrag im lokalen Demo-Konto bestehen.

Die neue Einzahlungsoberfläche samt Historie wurde bei 375 × 812 Pixeln visuell geprüft. Kein horizontaler Überlauf, Eingabefokus sichtbar. Im geprüften Browserzustand keine JavaScript-Warnungen oder -Fehler.

Grenzen: keine echte Scheinannahme, keine Auszahlung, kein Double-Entry-Ledger, keine Datenbank-Revisionssicherheit. Doppelanfragen wurden sequenziell getestet; keine parallelen Lasttests oder PostgreSQL-Validierung. Kein vollständiges Accessibility- oder Sicherheitsaudit.

---

# Historisches Prüfprotokoll — v0.2

Abgeschlossen am 10. September 2026. Die unten aufgeführten Paketversionen aus v0.1 wurden beibehalten; keine neuen Abhängigkeiten für v0.2.

## Automatisierte Prüfung

- `composer test`: **28 Tests, 153 Assertions erfolgreich** unter PHP 8.4.25; zuletzt am 10. September erneut ausgeführt.
- `vendor/bin/pint --test`: erfolgreich.
- `npm run build`: erfolgreich, einschließlich strikter TypeScript-Prüfung; separate Chunks für Startseite, Anmeldung, Sitzung und App-Shell.
- `composer validate --strict`: erfolgreich.
- Neue lokale Migrationen für Customer, Account und Card sowie Demo-Seeding erfolgreich ausgeführt.

Die Tests decken öffentliche Kartenfelder, PIN-Hashing, führende PIN-Nullen, Session-ID-Wechsel, persistierte Fehlversuche, Kartensperre und Ablauf der Sperre, gesperrte/abgelaufene Karten, gesperrte Konten, ungültige PIN-Formate, unbekannte Karten, IP-Limit, geschützten Zugriff, Inaktivitätsgrenze, nachträgliche Kartensperre, Abmeldung, fehlgeschlagenen Kartenwechsel und wiederholtes Seeding ab. Dazu kommen die bisherigen HTTP-/Inertia-/SQLite-Tests. PINs erscheinen auch bei Validierungsfehlern nicht in Laravels alten Formulareingaben.

## Browserdurchlauf

Im integrierten Browser tatsächlich durchlaufen:

1. Von der Landingpage zur Kartenauswahl navigiert.
2. DEMO-002 gewählt, falsche PIN eingegeben: Fehlermeldung erscheint, Fokus liegt wieder im PIN-Feld.
3. Mit `0042` angemeldet: Sitzung für Sam Beispiel und Konto DEMO-002 sichtbar.
4. Sitzungsseite bei 375 × 812 Pixeln visuell geprüft; kein horizontaler Seitenüberlauf.
5. Sitzung beendet: Bestätigung und leeres Anmeldeformular erscheinen.
6. Browser-Zurück nach Abmeldung: erneute Anmeldung wird verlangt, keine geschützten Sitzungsdaten sichtbar.
7. Im abschließend geprüften Browserzustand keine JavaScript-Warnungen oder -Fehler.

Der TypeScript-Typ des Browser-Timers wurde beim Build korrigiert. Die temporäre Browsergröße wird nach der Prüfung zurückgesetzt.

## Grenzen

- Kein fünfminütiger Echtzeit-Browsertest des Timers; die serverseitige Ablaufgrenze ist mit kontrollierter Testzeit geprüft. Hintergrund-Tabs können die Anzeige verzögern, die serverseitige Zugriffskontrolle bleibt maßgeblich.
- Kein vollständiges Sicherheits-, Accessibility- oder Cross-Browser-Audit, keine Prüfung auf realen Mobilgeräten.
- SQLite-Tests bestätigen keine PostgreSQL-Sperrsemantik oder verteilte Nebenläufigkeit. Kein Lasttest.
- Demo-Zugangsdaten sind öffentlich bekannte Lernwerte. Keine echten Bankdaten oder Geldbewegungen.
- Kein Remote-Repository, Deployment oder CI-Lauf. Weiterhin vorhandene npm-Warnung zur globalen `//prefix`-Konfiguration; Build erfolgreich.

---

# Historisches Prüfprotokoll — v0.1

Geprüft am 9. September 2026 im Projekt `/Users/Wolfgang/developer/projects/LaravelCashMashine`.

## Tatsächliche Versionen

| Werkzeug / Paket | Version |
| --- | --- |
| PHP (für sämtliche finalen Prüfungen) | 8.4.25 |
| Composer | 2.10.3 |
| Laravel Framework | 13.31.0 |
| Laravel-Projektskelett | 13.10.1 |
| Inertia Laravel-Adapter | 3.3.3 |
| Inertia Vue-Adapter | 3.7.0 |
| Vue | 3.5.42 |
| TypeScript | 5.9.3 |
| Vite | 8.2.2 |
| Laravel Vite Plugin | 3.2.0 |
| Tailwind CSS | 4.3.3 |
| Pest | 4.7.0 |
| Pest Laravel Plugin | 4.1.0 |
| Node | 24.20.0 |
| npm | 11.19.0 |
| SQLite (PHP-Laufzeit) | 3.53.4 |

Ursprünglich aktiv waren PHP 8.5.10 und Node 18.18.2/npm 9.8.1. PHP 8.4 wurde separat installiert, Node 24 war schon vorhanden. Die globale Standardauswahl wurde nicht umgeschaltet.

## Ausgeführte Prüfungen

- `composer test`: **9 Tests, 34 Assertions erfolgreich**. Redirect, deutsche HTML-Hülle, Inertia-Komponente/Props, Inertia-Navigation mit aktueller Asset-Version, vollständiger Reload bei veralteter Version, Healthcheck, drei 404-Fälle und isolierte SQLite-Migrationen.
- Testlauf zusätzlich ohne `public/build/manifest.json` erfolgreich. Die vorübergehend beiseitegelegte Manifestdatei wurde wiederhergestellt.
- `npm run build`: **erfolgreich**, einschließlich `vue-tsc --noEmit`; Vite erzeugt Manifest, CSS und JavaScript einschließlich separatem Seiten-Chunk.
- `vendor/bin/pint --test`: **erfolgreich**.
- `composer validate --strict`: **erfolgreich**.
- `composer check-platform-reqs`: **erfolgreich unter echtem PHP 8.4.25**, einschließlich expliziter PDO-/SQLite-Anforderungen.
- Lokale Infrastruktur-Migrationen ausgeführt; separate Migrationstests laufen auf SQLite `:memory:`.
- Echter HTTP-Aufruf am lokalen Laravel-Server: `/` führt auf `/atm`, `/atm` und `/up` antworten mit 200; referenzierte gebaute CSS- und JavaScript-Dateien antworten mit 200.

Ein bei der Prüfung nach dem Build aufgedeckter Testfehler wurde korrigiert: Inertia antwortet bei fehlender/veralteter Asset-Version erwartungsgemäß mit 409. Der Navigationstest liest jetzt die Version aus dem initialen Seitenaufruf; ein eigener Test prüft den Reload-Fall.

## Grenzen und verbleibende Hinweise

- Ergänzende Browserprüfung durchgeführt: Desktop (1280 × 900), Mobilansicht (375 × 812) und sehr schmale Ansicht (320 × 740). Die bei 320 Pixeln zu breite Überschrift wurde durch eine kleinere Grundschrift korrigiert und anschließend visuell nachgeprüft. Kein horizontaler Seitenüberlauf in den geprüften Breiten.
- Ausblick-Anker und Startseitenlink geprüft; Sprunglink per Enter setzt den Fokus auf `main`. Tab-Navigation vom Sprunglink erreicht den Markenlink mit sichtbarer Fokusumrandung. Kartenauswahl ist tatsächlich deaktiviert. Im geprüften Browserzustand keine JavaScript-Warnungen oder -Fehler.
- Dies ist eine begrenzte Browserprüfung, keine vollständige Accessibility- oder Cross-Browser-Zertifizierung. Screenreader, reale Mobilgeräte und weitere Browser wurden nicht geprüft.
- Keine Fachfunktionen implementiert oder getestet; keine Aussage über Banking-Sicherheit, Buchungsrichtigkeit oder konkurrierende Auszahlungen.
- Kein PostgreSQL-, CI- oder Deployment-Test. Kein Remote-Repository und kein Git-Commit angelegt.
- npm meldet eine bereits vorhandene globale Konfiguration `//prefix` als unbekannt; Installation und Build funktionieren. Diese globale Einstellung wurde nicht verändert.
- Paketinstallationen meldeten keine bekannten Schwachstellen. Das ist eine Momentaufnahme der Paketquellen, keine Sicherheitszertifizierung der Anwendung.
- Der lokale PHP-Server wurde für den HTTP-Test gestartet. Dauerhafter Betrieb erfolgt über die Startanleitung in der README.

## Nachprüfung nach der mobilen Korrektur

`npm run build` einschließlich TypeScript-Prüfung erfolgreich. `composer test` erneut mit 9 Tests und 34 Assertions erfolgreich; `vendor/bin/pint --test` ebenfalls erfolgreich. Die temporäre Browser-Größenvorgabe wurde zurückgesetzt.
