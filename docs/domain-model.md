# Fachmodell

**Status v0.5: Customer, Account, Card, Transaction, ATM und CashInventory sind implementiert.**

v0.5 verwendet `deposit`, `withdrawal` und bei übernommenen positiven Salden `opening`. Anfangsbuchungen haben keine Karte und keinen Automaten. Einzahlungen haben keine ATM-Zuordnung, weil keine Scheinannahme simuliert wird. Auszahlungen speichern den verwendeten Automaten und die ausgegebene Scheinverteilung. Jede Buchung besitzt eine stabile Belegreferenz; Zugriffe auf Belege werden über das aktive Sitzungskonto begrenzt. Das Modell bildet weiterhin keine vollständige Bankbuchhaltung ab; die Anwendung bleibt eine Simulation mit erfundenen Daten.

## Beziehungen

```text
Customer 1 ─── n Account 1 ─── n Card
                    │              │
                    1              1
                    │              │
                    n              n
                 Transaction ──── ATM
                                  1 │
                                    n
                              CashInventory
```

Präzisierung: Jedes Account gehört zunächst genau einem Customer. Jede Card gehört genau einem Account und erhält dessen Eigentümer indirekt. Jede ATM-Buchung gehört zu genau einem Account, einer Card und einem ATM. Ein ATM hat viele Transactions sowie je Stückelung einen CashInventory-Eintrag. Eröffnungsbestände werden gesondert definiert; sie sind keine erfundenen Kartenbuchungen.

## Entitäten

| Konzept | Verantwortung | Vorgeschlagene Felder | Regeln / Abgrenzung |
| --- | --- | --- | --- |
| Customer | Fiktiver Kontoinhaber | `id`, `display_name`, Zeitstempel | Keine echten personenbezogenen Daten. Noch offen, ob zusätzlich eine Web-Anmeldung nötig ist. |
| Account | EUR-Konto und verfügbarer Saldo | `id`, `customer_id`, `reference` (eindeutig), `currency`, `balance_minor`, `status` | Beträge als Integer-Centwerte; kein Float. Zunächst kein Dispo, nur aktive Konten buchbar. Ein gespeicherter Saldo muss mit Buchungen atomar konsistent bleiben. |
| Card | Zugang zu genau einem Account | `id`, `account_id`, `demo_reference` (eindeutig), `pin_hash`, `status`, `failed_attempts`, `locked_until`, optional `expires_at` | Keine echte PAN/CVV. PIN als Zeichenfolge behandeln (führende Nullen), nur gehasht speichern, nie als Inertia-Prop oder Log ausgeben. Sperrpolitik noch offen. |
| Transaction | Nachvollziehbare erfolgreich gebuchte Geldbewegung | `id`, `receipt_reference` (eindeutig), `account_id`, optional `card_id`, optional `atm_id`, `type`, `amount_minor`, `currency`, `balance_after_minor`, `idempotency_key` (eindeutig je Konto), optional `cash_breakdown`, `created_at` | Positiver Betrag; Vorzeichenwirkung ergibt sich aus `withdrawal`/`deposit`. Erfolgreiche Buchungen unveränderlich; Korrekturen später als Gegenbuchung. Abgelehnte Versuche zunächst keine Buchung; Audit-Konzept offen. |
| ATM | Simulierter Automat | `id`, `code` (eindeutig), `label`, `status`, `currency` | Zunächst ein Demo-Automat; Status muss vor Geldbewegungen geprüft werden. Limits und Einzahlungsfähigkeit noch offen. |
| CashInventory | Scheine je Automat und Stückelung | `id`, `atm_id`, `denomination_minor`, `quantity` | Unique auf `(atm_id, denomination_minor)`. Stückelung positiv, Anzahl ganzzahlig und ≥ 0. Geldbestand = Summe aus Stückelung × Anzahl. |

`ATM` ist der fachliche Name; die PHP-Klasse heißt `Atm` und verwendet die Laravel-konforme Tabelle `atms`. IDs sind konventionelle Laravel-Bigints; es gibt keine abstrakten Identifier-Objekte. `User` im aktuellen Gerüst ist ausschließlich Laravel-Infrastruktur und **keine** Identitätszuordnung für Customer oder Card.

## Ablauf einer Auszahlung

1. Aktive Kartensitzung, Kontozuordnung und aktiven Automaten prüfen.
2. Betrag serverseitig validieren: ganze Euro, 10,00 bis 1.000,00 EUR.
3. In einer Datenbanktransaktion Account und relevante Bargeldbestände konsistent lesen/sperren.
4. Deckung und eine tatsächlich mögliche Scheinkombination prüfen. Ein ausreichender Gesamtbestand allein reicht nicht.
5. Kontosaldo und Scheinanzahlen aktualisieren, erfolgreiche Transaction mit eindeutigem Wiederholungsschlüssel anlegen, alles gemeinsam committen.
6. Ergebnis aus dem bestätigten Datenbestand anzeigen. Mehrfachklicks dürfen keine zweite Buchung erzeugen.

Jeder Fehler vor dem Commit lässt Saldo, Bestand und Buchungen unverändert. Hier wird keine physische Geldausgabe behauptet; Hardwarefehler und verteilte Transaktionen sind außerhalb des Lernumfangs. Einzahlungsregeln (insbesondere Scheinannahme) müssen vor Umsetzung separat festgelegt werden.

## Wichtige Grenzen

- Der Entwurf ist noch kein Double-Entry-Ledger. Vor komplexeren Buchungen muss entschieden werden, ob ein einfacher Saldo plus Transaktionsliste genügt.
- SQLite eignet sich lokal für den Einstieg, beweist aber keine PostgreSQL-Sperrsemantik. Vor Nebenläufigkeitsfunktionen ist eine eigene Prüfung mit der späteren Ziel-Datenbank nötig.
- Die Scheinverteilung minimiert die Zahl der Scheine unter Beachtung des vorhandenen Bestands. Sie bevorzugt bei gleicher Anzahl nicht ausdrücklich eine bestimmte Stückelung; diese Produktregel ist offen.
- Fremdschlüssel und Unique-Constraints sichern die zentralen Beziehungen. Ein vollständiger datenbankseitiger Schutz gegen jede direkte SQL-Manipulation ist nicht umgesetzt.
- Kunden-, Konto- und Kartendaten sind nicht pauschal an das Frontend weiterzugeben. Spätere Antworten benötigen eine explizite Auswahl öffentlicher Felder.

## Umsetzung ohne unnötige DDD-Komplexität

Die Umsetzung verwendet konventionelle Eloquent-Modelle, Migrationen, Form Requests und kleine Controller. `WithdrawMoney` kapselt die zusammengehörigen Änderungen an Konto, Bargeldbestand und Buchung. Es gibt keine vorsorglichen Repositories, Aggregate-Basisklassen, CQRS- oder Event-Sourcing-Infrastruktur.

Tests decken falsche/gesperrte PIN, fremdes Konto, abgelaufene Sitzung, fehlende Deckung, nicht darstellbaren Betrag, begrenzten Bestand, Rollback bei Fehler und wiederholte Requests ab. Parallelzugriffe mit PostgreSQL bleiben ungeprüft.
