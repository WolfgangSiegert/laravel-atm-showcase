# Hosting-Empfehlung für den öffentlichen Showcase

Stand: 14. September 2026; Free-Instanzgrenzen erneut geprüft. Kostenlose Tarife können sich ändern und müssen vor dem tatsächlichen Deployment erneut geprüft werden.

## Empfehlung

| Aufgabe | Dienst | Tarif | Begründung |
| --- | --- | --- | --- |
| Quellcode und Pull Requests | GitHub | Free, öffentliches Repository | Geeignet für Versionsverwaltung und Portfolio-Präsentation. |
| CI | GitHub Actions | Standard-Runner im öffentlichen Repository | Laut GitHub für öffentliche Repositories kostenlos. |
| Laravel-Webdienst | Koyeb | Free Web Service, Region Frankfurt | Unterstützt PHP, GitHub-basierte Deployments und Docker; verwaltetes TLS und eine öffentliche `koyeb.app`-Adresse. |
| PostgreSQL | Neon | Free | Kostenloser Einstieg; laut offizieller Free-FAQ 0,5 GB Speicher und 100 **CU-Stunden** pro Projekt und Monat. CU-Stunden sind eine Compute-Einheit, keine pauschalen Laufzeitstunden. |

GitHub Pages ist für diese Anwendung nicht geeignet. Es veröffentlicht statische HTML-, CSS- und JavaScript-Dateien, führt aber kein dauerhaftes Laravel-/PHP-Backend aus und stellt keine PostgreSQL-Datenbank bereit. GitHub bleibt trotzdem der richtige Ort für Repository, Review und CI.

## Grenzen der kostenlosen Empfehlung

- Die Koyeb-Free-Instanz hat 512 MB RAM, 0,1 vCPU und 2 GB nicht persistenten lokalen Speicher. Nach einer Stunde ohne Traffic skaliert sie auf null; der dokumentierte Kaltstart liegt typischerweise bei 1–5 Sekunden.
- Koyeb verlangt für den Starter-Account eine gültige Zahlungsmethode. Der Free-Web-Service selbst soll nicht berechnet werden; Koyeb bietet derzeit jedoch noch kein allgemeines Ausgabenlimit. Es dürfen deshalb keine kostenpflichtigen Instanzen oder Zusatzdienste angelegt werden.
- Die lokale SQLite-Datei darf online nicht verwendet werden, weil der Dateispeicher der Free-Instanz nicht persistent ist. Sessions, Cache und fachliche Daten müssen für die öffentliche Instanz in geeignete persistente Dienste verlagert werden.
- Neon Free ist für einen kleinen Showcase ausreichend, aber ohne Produktions-SLA. Bei Überschreitung der kostenlosen Compute- oder Transfergrenzen kann die Datenbank bis zum nächsten Abrechnungszeitraum aussetzen.
- Eine eigene Domain verursacht gegebenenfalls Registrierungsgebühren. Für den kostenlosen Start genügt die bereitgestellte `koyeb.app`-Adresse.

Diese Kombination ist für einen Portfolio-Showcase vertretbar, aber keine kostenlose Produktionsgarantie. Der Deployment-Prototyp muss Speicherverbrauch, Kaltstart, Datenbankverbindungen und den Demo-Reset tatsächlich messen.

Der vorbereitete Container und die Abnahme stehen in [deployment.md](deployment.md). Der Demo-Reset erfolgt beim ersten Besucher nach Ablauf des 24-Stunden-Intervalls und benötigt deshalb keinen bezahlten oder ständig aktiven Worker. Koyeb beschreibt Free ausdrücklich als Angebot für Hobby-/Testprojekte, nicht als Produktionsdienst.

## Alternative

Render Free plus Neon Free ist einfacher dokumentiert, weil Render eine konkrete Laravel-Docker-Anleitung anbietet. Für diesen Showcase ist es die zweite Wahl: Der kostenlose Webdienst schläft bereits nach 15 Minuten ein und kann laut Render ungefähr eine Minute zum Aufwachen benötigen. Das ist bei einem Portfolio-Link deutlich störender. Render Free Postgres läuft außerdem nach 30 Tagen ab, weshalb auch dort Neon oder ein anderer externer PostgreSQL-Dienst nötig wäre.

## Offizielle Quellen

- [GitHub Pages ist statisches Hosting](https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages)
- [GitHub-Actions-Abrechnung](https://docs.github.com/en/billing/concepts/product-billing/github-actions)
- [Koyeb Free Instances](https://www.koyeb.com/docs/reference/instances)
- [Koyeb Scale-to-Zero](https://www.koyeb.com/docs/run-and-scale/scale-to-zero)
- [Koyeb PHP-Deployment](https://www.koyeb.com/docs/deploy/php)
- [Koyeb-Deployment aus GitHub](https://www.koyeb.com/docs/build-and-deploy/deploy-with-git)
- [Neon-Preise](https://neon.com/pricing)
- [Neon Free-Quoten](https://neon.com/faqs/free-plan-limits-and-quotas)
- [Render Free](https://render.com/docs/free)
- [Render Laravel mit Docker](https://render.com/docs/deploy-php-laravel-docker)
