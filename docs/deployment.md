# Deployment — v1.0

Stand: 22. September 2026. Das Neon-Projekt `steep-shape-34891524` ist mit dem lokalen Projekt und dem Branch `production` verknüpft. Die leere Deployment-Policy in `neon.ts` ist die dokumentierte Ausgangsbasis. Die lokale Anwendung verwendet weiterhin SQLite; die öffentliche Render-/Neon-Instanz ist unter `https://atm.tiny-bits.org` erreichbar. `https://lern-bank-geldautomat.onrender.com` bleibt als technische Rückfalladresse aktiv. Funktionaler Ablauf, Proxyverhalten, Kaltstart und Container-Speicher wurden am Zielhost geprüft.

## Ziel und Voraussetzungen

GitHub verwaltet Quellcode und CI. Der Deployment-Prototyp verwendet einen Docker-Webdienst auf Render Free in Frankfurt und eine ausschließlich fiktive PostgreSQL-Datenbank auf Neon Free. Ein einzelner Webdienst genügt. Kein Worker, Redis, persistentes lokales Volume oder kostenpflichtiger Zusatzdienst ist erforderlich.

Das Render-Konto muss vorab eingerichtet und mit GitHub verbunden werden. Zugangsdaten gehören ausschließlich in den Secret-Speicher des Hosts. Im Blueprint-Dialog ausdrücklich den vorbereiteten Free-Webdienst prüfen; keine Render-Datenbank oder kostenpflichtige Compute-Stufe ergänzen. Die Empfehlung ist keine Garantie kostenloser dauerhafter Verfügbarkeit; siehe [hosting.md](hosting.md).

## Konfiguration im Host

| Variable | Wert |
| --- | --- |
| `APP_NAME` | `LERN-Bank Mein Geldautomat` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Einmalig lokal mit `php artisan key:generate --show` erzeugen, als Secret speichern |
| `APP_URL` | Optional. Ohne Wert wird `https://$RENDER_EXTERNAL_HOSTNAME` verwendet. |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `de` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Neon-Verbindungs-URL mit `sslmode=require`; als Secret speichern |
| `SESSION_DRIVER` / `CACHE_STORE` | `database` |
| `SESSION_SECURE_COOKIE` / `SESSION_HTTP_ONLY` | `true` |
| `SESSION_SAME_SITE` | `lax` |
| `PUBLIC_DEMO_ENABLED` | `true` ausschließlich für diese Demo-Datenbank |
| `PUBLIC_ADMIN_GUEST_ENABLED` | `true` für den passwortlosen, strikt schreibgeschützten Admin-Showcase; `false` deaktiviert und entzieht den Gastzugang beim nächsten Start |
| `USAGE_METRICS_ENABLED` / `USAGE_METRICS_RETENTION_DAYS` | `true` / `400` aktiviert cookielose Tageszähler und bewahrt ungefähr 13 Monate auf; lokal ist die Funktion standardmäßig deaktiviert |
| `PORTFOLIO_TRAFFIC_ENABLED` / `PORTFOLIO_TRAFFIC_RETENTION_DAYS` | `true` / `90` aktiviert die zustandslose Portfolio-Erfassung und deren Aufbewahrungsfrist |
| `PORTFOLIO_TRAFFIC_RATE_LIMIT` | `60` akzeptierte oder abgewiesene Anfragen je ermittelter Client-IP und Minute |
| `SECURITY_HEADERS_ENABLED` | `true` |
| `TRUSTED_PROXIES` | `*`, für den ausschließlich über Render veröffentlichten Container geprüft |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stderr` / `warning` |
| `TRAFFIC_LOG_CHANNEL` | `traffic_stderr`, damit Info-Ereignisse trotz `LOG_LEVEL=warning` nach stderr gelangen |
| `QUEUE_CONNECTION` | `sync` (keine Hintergrundjobs vorgesehen) |
| `PORTFOLIO_URL` | Optional: öffentliche HTTPS-Adresse des persönlichen Portfolios; ohne Wert verweist die App auf dessen GitHub-Repository. |

`render.yaml` setzt `TRUSTED_PROXIES=*`, weil der Container nur über den verwalteten Render-Gateway veröffentlicht wird. Die Zielhost-Prüfung am 20. September 2026 bestätigte HTTPS-Erkennung, HSTS und sichere Cookies. Direkte Versuche mit `X-Forwarded-Proto: http` und `Forwarded: proto=http;host=attacker.invalid` änderten weder Status noch kanonische HTTPS-Asset-URLs oder HSTS. Damit ist die Manipulation des Schemas und Hosts über diese Besucherheader auf dem Render-Pfad ausgeschlossen. Die konkrete Client-IP lässt sich ohne Diagnose-Endpunkt nicht direkt beobachten; das IP-Limit bleibt deshalb keine vollständige Abuse-Abwehr. Laravels TrustProxies-Middleware liest die Konfiguration auch nach `config:cache`.

Die eigene Adresse verwendet bewusst die Subdomain `atm.tiny-bits.org`. Die Hauptdomain und `www` bleiben bei GitHub Pages; `tiny-bits.org/portfolio/` bleibt dadurch unverändert erreichbar. Im HostGator-DNS ist ausschließlich ein CNAME mit Name `atm` und Ziel `lern-bank-geldautomat.onrender.com` gesetzt. Kein A-, AAAA- oder Wildcard-Eintrag für diese Subdomain ergänzen. Render hat die Domain verifiziert und verwaltet ihr TLS-Zertifikat. Die Render-Subdomain bleibt als Rückfalladresse aktiviert.

Der `APP_KEY` bleibt über Neustarts und Deployments unverändert. `composer setup` gehört nicht in das Deployment: Es regeneriert den Schlüssel. Keine `.env`, lokalen SQLite-Dateien, Sitzungen oder lokalen Betreiberpasswörter werden ins Docker-Image kopiert.

## Erste Veröffentlichung

1. Das separate Neon-Projekt ist angelegt und sein Branch `production` verknüpft. Die direkte Verbindungs-URL mit TLS aus Neon kopieren; sie wird ausschließlich im Render-Dialog als `DB_URL` hinterlegt. Die lokale Entwicklungsdatenbank bleibt getrennt.
2. In Render über **New → Blueprint** das öffentliche GitHub-Repository verbinden. Render liest die Datei `render.yaml`; sie definiert einen Free-Webdienst in Frankfurt, Docker-Build, `/up` als Healthcheck und Deployments erst nach bestandenen GitHub-Checks.
3. Beim ersten Blueprint-Sync die beiden mit `sync: false` markierten Werte setzen: `APP_KEY` einmalig lokal mit `php artisan key:generate --show` erzeugen und `DB_URL` aus Neon einfügen. Beide Werte dürfen nicht ins Repository oder in Chat-Nachrichten kopiert werden.
4. Vor dem Erstellen prüfen, dass genau ein Webdienst mit Plan **Free** und keine Render-Datenbank angelegt wird. Der von Render gesetzte `PORT` wird vom Container verwendet; der Container-Fallback ist 10000.
5. Nur einen grünen CI-Stand deployen. Der Blueprint verwendet `autoDeployTrigger: checksPass`; Rollbackfähigen Commit notieren.
6. Beim Containerstart prüft `atm:deployment-check` Konfiguration und Datenbankverbindung. Danach folgen Config-Cache, additive Migrationen, idempotente Einrichtung der beiden Demo-Karten/ATM und des optionalen Read-only-Gasts sowie Route-/View-Cache. Bei einem Fehler startet Apache nicht. Der Gast besitzt kein veröffentlichtes Passwort und kann keine Schreibroute aufrufen.
7. Für den privaten Superadmin auf einem vertrauenswürdigen lokalen Checkout mit derselben Produktionsdatenbank `php artisan atm:operator-create deine-adresse@example.org` ausführen. Das Passwort wird verdeckt abgefragt, ist mindestens 16 Zeichen lang und steht weder im Repository noch in Prozessargumenten. Dafür die Produktionsvariablen nur in einer separaten, ignorierten Umgebung verwenden; keine lokalen Datenbankwerte überschreiben. Bestehende Betreiber werden durch die Rollen-Migration automatisch zu Superadmins.

Das Image verwendet PHP 8.4/Apache, Node 24 nur während des Builds und maximal vier Apache-Worker. Der erste öffentliche Lauf erreichte das frühere Limit von zwei Workern bereits durch Healthchecks und Besucherzugriffe; vier Worker sind der vorsichtige Folgewert für 512 MB. Render blendet Anwendungsmetriken im Free-Tarif aus und stellt dort keine Shell bereit. Deshalb protokolliert der Container nach dem Start zwei Minuten lang alle zehn Sekunden ausschließlich verwendeten, maximal beobachteten und verfügbaren cgroup-Speicher. Die zwölf Stichproben lagen bei rund 50 bis 56 MiB; der höchste beobachtete Wert betrug 58,1 MiB von 512 MiB. Kaltstarts wurden mit ungefähr 23 bis 28 Sekunden beobachtet. Beides sind Stichproben und keine zugesicherten Obergrenzen. Die Basisimages folgen ihren gepflegten Hauptversionen; PHP-/OS-Patchstände sind damit bewusst nicht auf einen unveränderlichen Digest festgeschrieben. Abhängigkeiten werden über beide Lockfiles fixiert.

## Demo-Reset und Aufbewahrung

`PUBLIC_DEMO_ENABLED=false` ist lokal der Standard. Bei aktivierter öffentlicher Demo richtet `atm:demo-provision` ausschließlich DEMO-001, DEMO-002 und BER-DEMO-01 ein; es setzt bestehende Salden nicht zurück. Alle Konten starten bei 0 €; Besucher beginnen mit einer Einzahlung.

24 Stunden nach dem letzten Reset setzt die nächste Anfrage an `/atm` oder `/atm/*` die bekannten Demo-Konten auf 0 €, löscht deren fiktive Buchungen und zugeordnete Audit-Ereignisse, hebt Kartensperren auf und stellt den Standard-Scheinbestand und aktiven Automatenstatus wieder her. Ein Versionszähler macht alte Kartensitzungen und vorbereitete Buchungsanfragen ungültig. Andere Konten, Benutzer und deren Buchungen bleiben erhalten. Audit-Ereignisse älter als sieben Tage werden im ausdrücklich aktivierten Demo-Modus ebenfalls verworfen. Das ist eine bewusst kurzlebige Simulation, kein revisionssicheres Archiv.

Der Reset arbeitet atomar und wird durch eine gesperrte Datenbankzeile auch bei parallelen Anfragen nur einmal ausgeführt. Er benötigt keinen ständig laufenden Scheduler: Nach einer Ruhephase erfolgt er beim nächsten Besucher. Ein erzwungener Reset ist ausschließlich per CLI möglich:

```sh
php artisan atm:demo-reset --force
php artisan atm:demo-reset --force --if-due
```

Diese Befehle löschen fiktive Daten. Sie gehören ausschließlich in die dedizierte Showcase-Umgebung. Es gibt keine öffentliche Reset-Route. IP-basierte Limits begrenzen Geldbewegungsanfragen auf 30 pro Minute zusätzlich zu den PIN-Limits; sie verhindern weder verteilten Missbrauch noch das vorübergehende Leeren gemeinsam genutzter Konten. Eine absolute Zeilen-/Traffic-Obergrenze ist nicht implementiert. Anbieterquoten vor Freigabe überwachen.

## Cookielose Nutzungsstatistik

Bei `USAGE_METRICS_ENABLED=true` erhöht die Anwendung ausschließlich Tageszähler in `usage_metrics`. Pro Datum und fest definiertem Metriknamen existiert höchstens eine Zeile. Erfasst werden öffentliche Start- und Seitenaufrufe sowie erfolgreiche ATM-Anmeldungen, Einzahlungen und Auszahlungen. Es gibt keine Wiedererkennung und keinen Consent-Cookie; die Anwendung speichert in dieser Tabelle insbesondere keine IP-Adresse, User-Agent-, Session-, Cookie- oder Benutzerkennung, URL, Querywerte oder Routenparameter.

Das private Superadmin-Dashboard zeigt die aggregierte Nutzung. Der öffentliche Read-only-Gast erhält diese Werte nicht. Vorabrufe, Healthchecks, Adminseiten, fehlgeschlagene Antworten und abgelehnte Anmeldungen erhöhen die Zähler nicht. Idempotent wiederholte Ein- oder Auszahlungen werden nur einmal gezählt. Statistikfehler werden protokolliert, lassen aber weder Seitenaufrufe noch Anmeldungen oder Buchungen scheitern. Der Demo-Reset verändert die Nutzungszähler nicht.

Diese Zahlen messen technische Aufrufe und erfolgreiche Aktionen, nicht unterschiedliche Personen oder Sitzungen. Bei jedem erfolgreichen Inkrement entfernt die Anwendung Tageszeilen außerhalb der konfigurierten Aufbewahrung; der Standard von 400 Tagen ermöglicht ungefähr 13 Monate Vergleich. Die cookielose Gestaltung vermeidet für diesen Vorgang das Speichern oder Auslesen einer Analysekennung auf dem Endgerät. Sie ist deshalb datensparsamer, aber weder pauschal „DSGVO-frei“ noch eine Garantie für Einwilligungsfreiheit: Hosting, kurzzeitige technische IP-Verarbeitung, Rechtsgrundlage, Zweck und Informationspflichten sind für den konkreten Betrieb weiterhin zu prüfen und in der Datenschutzerklärung transparent zu beschreiben.

## Portfolio-Traffic

Die Portfolio-Erfassung ist fachlich und technisch von den aggregierten ATM-Zählern getrennt. Render aktiviert `PORTFOLIO_TRAFFIC_ENABLED=true`; die additive Migration legt `portfolio_traffic_events` in Neon an. Ein akzeptierter Request speichert nur UTC-Zeitpunkt, Site, freigegebenen Pfad und eine HMAC-Kennung. Rohe IP-Adresse und vollständiger User-Agent werden weder in der Tabelle noch in den strukturierten Traffic-Logs gespeichert.

Bei jedem akzeptierten Request werden Ereignisse gelöscht, deren Zeitpunkt mehr als `PORTFOLIO_TRAFFIC_RETENTION_DAYS` zurückliegt. Das benötigt keinen Worker und ist bei parallelen Löschvorgängen idempotent. Ohne neue akzeptierte Requests kann die physische Löschung entsprechend später erfolgen. Die Auswertung unter `/admin/portfolio-traffic` ist ausschließlich für private Superadmins freigegeben; der öffentliche Viewer erhält HTTP 403 und keine Inertia-Props mit Portfolio-Daten.

Nach einem Deployment zuerst den erfolgreichen Start samt Migration prüfen. Anschließend je einen minimalen POST mit erlaubtem und unerlaubtem Origin senden, die 204-/403-Antwort kontrollieren und die private Auswertung mit einem Superadmin prüfen. Erst danach soll das statische Portfolio den Client-Aufruf veröffentlichen. Der genaue Vertrag und die Genauigkeitsgrenzen stehen in [portfolio-traffic.md](portfolio-traffic.md).

## Abnahme und Wiederherstellung

- HTTPS öffnet ohne Zertifikatsfehler; Assets werden ohne CSP-Fehler geladen.
- Login, Einzahlung mit Zweck, Auszahlung, beide Belege, Filter/Sortierung und Abmeldung funktionieren an der öffentlichen Adresse.
- Session-Cookie ist Secure/HttpOnly/SameSite=Lax; HSTS, CSP und Basisheader sind vorhanden. Proxy-Header einer direkten Besucheranfrage dürfen die ermittelte IP nicht beliebig überschreiben.
- Bekannte lokale Betreiberzugänge sind öffentlich ungültig. `/operator` ist geschützt; Fehlerseiten zeigen keine Debug-Daten.
- Erzwungener Demo-Reset invalidiert eine offene Sitzung und stellt Saldo/Scheine wieder her; ein normaler Neustart erhält Daten und `APP_KEY`.
- Speicherverbrauch, Datenbankverbindungen und Free-Quoten weiter beobachten. Gemessene Kaltstarts dauerten ungefähr 23 bis 28 Sekunden; weitere Starts können abweichen. `/up` bestätigt den Webprozess, ist aber kein vollständiger Datenbank-/Buchungsmonitor. Keine dauernden externen Keepalive-Abfragen einsetzen.

Für ein Code-Rollback den letzten grünen Commit mit unveränderten Secrets deployen. Bei einer Migration zuerst deren Kompatibilität prüfen; das automatische Zurückrollen oder Löschen von Migrationen ist kein Wiederherstellungsweg. Eine kaputte fiktive Demo kann auf einer **neuen dedizierten** Neon-Datenbank migriert/provisioniert werden; URL als Secret aktualisieren. Es werden keine echten Geschäftsdaten aufbewahrt. Wer Demo-Verläufe erhalten möchte, muss vor dem Reset getrennt sichern; derzeit ist kein bezahlter Backupdienst vorgesehen.

## CI und lokale Containerprüfung

GitHub Actions prüft PHP 8.4, Node 24, SQLite-Tests, sieben PostgreSQL-Szenarien einschließlich Schema- und Mehrprozessprüfung der Portfolio-Erfassung, Pint, TypeScript/Build und den Browserablauf in Chromium. Ein zweiter Job baut das Docker-Image und startet es gegen einen isolierten PostgreSQL-Service mit Produktionskonfiguration. Ein grüner Containerjob bestätigt Installation und HTTP-Start, aber keine Render-/Neon-Eigenschaften.

Lokal lässt sich das Image mit `docker build -t atm-showcase .` bauen. Der Docker-Daemon muss laufen. Der Containerstart benötigt die oben genannten Produktionsvariablen und eine ausschließlich fiktive PostgreSQL-Datenbank. Der Laravel-Entwicklungsserver bleibt für die normale SQLite-Vorführung vorgesehen.

Referenzen: [Render Blueprint](https://render.com/docs/blueprint-spec), [Render Free](https://render.com/docs/free), [Neon Free](https://neon.com/faqs/free-plan-limits-and-quotas), [Laravel-Docker-Beispiel bei Render](https://render.com/docs/deploy-php-laravel-docker), [PHP CI](https://github.com/shivammathur/setup-php), [Node CI](https://github.com/actions/setup-node).
