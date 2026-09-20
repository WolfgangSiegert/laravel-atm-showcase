# Hosting-Empfehlung für den öffentlichen Showcase

Stand: 20. September 2026. Kostenlose Tarife können sich ändern und müssen vor späteren Neuaufsetzungen erneut geprüft werden.

## Empfehlung

| Aufgabe | Dienst | Tarif | Begründung |
| --- | --- | --- | --- |
| Quellcode und Pull Requests | GitHub | Free, öffentliches Repository | Versionsverwaltung, Review und Portfolio-Präsentation. |
| CI | GitHub Actions | Standard-Runner im öffentlichen Repository | Prüft Anwendung, PostgreSQL-Semantik, Browserablauf und Container. |
| Laravel-Webdienst | Render | Free Web Service, Region Frankfurt | Unterstützt Docker aus GitHub, verwaltetes TLS und eine öffentliche `onrender.com`-Adresse. |
| PostgreSQL | Neon | Free | Das getrennte Showcase-Projekt ist bereits eingerichtet; die Daten bleiben unabhängig vom flüchtigen Webcontainer erhalten. |

GitHub Pages und Netlify Static Hosting führen kein dauerhaftes Laravel-/PHP-Backend aus. Eine Aufteilung in statisches Frontend und serverlose APIs würde das Projekt grundlegend umbauen und ist für diesen Showcase nicht sinnvoll.

Koyeb war zunächst vorgesehen, scheidet für eine kostenlose Neuinstallation aber aus. Seit der angekündigten Übernahme durch Mistral können neue Nutzer nur noch kostenpflichtige Pläne buchen; der bisherige Starter-Plan bleibt lediglich für bestehende Organisationen erhalten.

## Grenzen der kostenlosen Empfehlung

- Render Free stellt 512 MB RAM und 0,1 CPU bereit. Nach 15 Minuten ohne eingehende Anfrage schläft der Dienst; das Aufwachen kann ungefähr eine Minute dauern.
- Pro Workspace stehen monatlich 750 kostenlose Instanzstunden zur Verfügung. Build-Minuten und ausgehender Datenverkehr haben eigene Freigrenzen. Die Quoten im Dashboard beobachten; keine kostenpflichtige Compute-Stufe auswählen.
- Das lokale Dateisystem ist flüchtig. Die Anwendung speichert Sessions, Cache und Fachdaten deshalb in Neon PostgreSQL. Es gibt keine Upload-Funktion und kein persistentes Volume.
- Render kann Free-Dienste neu starten und bietet dafür kein Produktions-SLA. Neon Free hat ebenfalls Anbieterquoten und kein Produktions-SLA.
- Eine eigene Domain kann Registrierungsgebühren verursachen. Für den kostenlosen Start genügt die bereitgestellte `onrender.com`-Adresse.

Diese Kombination ist für einen Portfolio-Showcase vertretbar, aber keine Garantie dauerhaft unveränderter kostenloser Bedingungen. Veröffentlichung, Datenbankverbindung und Proxyverhalten sind geprüft; Kaltstart und Speicherverbrauch müssen noch gemessen werden.

Der vorbereitete Container und die Abnahme stehen in [deployment.md](deployment.md). Der Demo-Reset erfolgt beim ersten Besucher nach Ablauf des 24-Stunden-Intervalls und benötigt keinen Worker oder externen Keepalive-Dienst.

## Offizielle Quellen

- [Render Free](https://render.com/docs/free)
- [Render Docker](https://render.com/docs/docker)
- [Render Laravel mit Docker](https://render.com/docs/deploy-php-laravel-docker)
- [Render Blueprint-Spezifikation](https://render.com/docs/blueprint-spec)
- [Koyeb/Mistral-Ankündigung und Einschränkung für neue Nutzer](https://www.koyeb.com/blog/koyeb-is-joining-mistral-ai-to-build-the-future-of-ai-infrastructure)
- [Neon-Preise](https://neon.com/pricing)
- [Neon Free-Quoten](https://neon.com/faqs/free-plan-limits-and-quotas)
