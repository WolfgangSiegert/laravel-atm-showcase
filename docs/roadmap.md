# Roadmap bis v1.0

Ziel ist eine lokal vorführbare und öffentlich erreichbare ATM-Simulation. Sie verwendet ausschließlich erfundene Demo-Daten und erhält keine Anbindung an Banken, Zahlungssysteme oder echte Automatenhardware.

## Verbindlicher v1.0-Umfang

Ein Besucher kann eine Demo-Karte wählen, sich mit einer veröffentlichten Demo-PIN anmelden, Kontostand und Historie sehen, Demo-Geld einzahlen, eine durch Guthaben und Scheinbestand gedeckte Auszahlung ausführen, einen Beleg öffnen und die Sitzung sicher beenden. Eine geschützte Betreiberoberfläche verwaltet Automatenstatus und Bargeldbestand. Geldbewegungen bleiben atomar und nachvollziehbar.

## Meilensteine

| Version | Ziel | Abnahmekriterium |
| --- | --- | --- |
| v0.5 ✓ | Beleg und Abschluss | Jede neue Geldbewegung erhält eine stabile Referenz, eine kontogebundene Ansicht und eine druckfreundliche Darstellung. |
| v0.6 ✓ | Buchungsdetails und Übersicht | Ein- und Auszahlungen akzeptieren einen optionalen Verwendungszweck. Die Transaktionsübersicht filtert nach Typ und sortiert serverseitig nach Datum oder Betrag. |
| v0.7 ✓ | Audit und Betreiberbereich | Relevante Ereignisse werden datensparsam gespeichert. Authentifizierte Betreiber können Bestand und Automatenstatus verwalten; jede Änderung wird protokolliert. |
| v0.8 ✓ | PostgreSQL und Nebenläufigkeit | Migrationen und parallele Auszahlungen sind auf PostgreSQL geprüft; Doppelbelastung und negativer Bestand werden verhindert. |
| v0.9 | Qualität und Härtung | Der Hauptablauf ist im Browser automatisiert, per Tastatur bedienbar und für typische Fehlerzustände verständlich. Produktionswerte und Security-Header sind geprüft. |
| v1.0 | Veröffentlichung | CI, reproduzierbare Installation, Demo-Reset, Lizenz, Deployment-Dokumentation und öffentlich erreichbare Showcase-Instanz sind fertig. |

## Durch das öffentliche Showcase verpflichtend

- HTTPS und sichere Cookies in Produktion.
- PostgreSQL als Produktionsdatenbank mit gesicherter Nebenläufigkeit.
- Ein Betreiberbereich mit eigener Authentifizierung, Rate Limits und Berechtigungsprüfung.
- Keine öffentlich änderbaren Seed-, ATM- oder Bestandsverwaltungsrouten.
- Begrenzung beziehungsweise regelmäßiges Zurücksetzen öffentlicher Demo-Daten, damit Besucher die Demo nicht dauerhaft leeren oder sperren können.
- Produktionskonfiguration ohne Debug-Ausgaben, mit passenden Security-Headern, Fehlerseiten, Logging und Verfügbarkeitsprüfung.
- Automatisierte CI-Prüfung und dokumentierter Deployment- und Wiederherstellungsweg.

Die vorläufige Hosting-Empfehlung ist GitHub für Repository und CI, Koyeb Free für den Laravel-Webdienst und Neon Free für PostgreSQL. Grenzen und Alternativen stehen in [hosting.md](hosting.md). Die lokale PostgreSQL-Semantik ist bestätigt; Anbietergrenzen und Deployment werden im späteren Veröffentlichungsprototyp bestätigt oder verworfen.

## Zusätzliche Buchungsfunktionen

- Der Verwendungszweck ist bei Ein- und Auszahlung optional, wird serverseitig in Länge und Inhalt begrenzt und als unveränderlicher Teil der Transaction sowie des Belegs gespeichert.
- Die Übersicht filtert mindestens nach Einzahlung und Auszahlung. Anfangsbestände können separat ein- oder ausgeblendet werden.
- Sortiert wird serverseitig nach Datum oder Betrag, jeweils auf- und absteigend. Filter und Sortierung bleiben in der URL erhalten und gelten damit auch über paginierte Seiten hinweg.
- Freitextsuche ist für v1.0 nicht erforderlich. Falls sie ergänzt wird, soll sie nur den Verwendungszweck und die Belegreferenz durchsuchen.

## Bewusst außerhalb von v1.0

- Echte Bank-, Karten- oder Kundendaten
- Bank- und Zahlungsanbieter
- Überweisungen, Kredite und Dispositionsrahmen
- Mehrere Währungen oder Gemeinschaftskonten
- Double-Entry-Ledger
- Hardwareanbindung
- Microservices, CQRS und Event Sourcing
- Native mobile Apps

Das einfache Saldo-plus-Historie-Modell bleibt für v1.0 bestehen, solange PostgreSQL-Tests seine Konsistenz für die vorgesehenen Abläufe bestätigen. Es ist keine Grundlage für ein reales Finanzsystem.
