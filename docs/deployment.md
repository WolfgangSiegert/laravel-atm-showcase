# Deployment — v1.0 Release Candidate

Stand: 20. September 2026. Das Neon-Projekt `steep-shape-34891524` ist mit dem lokalen Projekt und dem Branch `production` verknüpft. Die leere Deployment-Policy in `neon.ts` ist die dokumentierte Ausgangsbasis. Die lokale Anwendung verwendet weiterhin SQLite; eine öffentliche App-Instanz und die Koyeb-Verbindung sind noch nicht eingerichtet. v1.0 wird erst nach erfolgreicher Prüfung am Zielhost freigegeben.

## Ziel und Voraussetzungen

GitHub verwaltet Quellcode und CI. Der Deployment-Prototyp verwendet einen Docker-Webdienst auf Koyeb Free in Frankfurt und eine ausschließlich fiktive PostgreSQL-Datenbank auf Neon Free. Ein einzelner Webdienst genügt. Kein Worker, Redis, persistentes lokales Volume oder kostenpflichtiger Zusatzdienst ist erforderlich.

Die kostenlosen Konten müssen vorab eingerichtet werden; Zugangsdaten gehören ausschließlich in den Secret-Speicher des Hosts. Die Free-Auswahl im Anbieter-Dialog prüfen. Koyeb verlangt eine Zahlungsmethode, hat aber laut aktueller Dokumentation kein allgemeines Ausgabenlimit. Es wird kein bezahlter Plan vorausgesetzt. Die Empfehlung ist keine Garantie kostenloser dauerhafter Verfügbarkeit; siehe [hosting.md](hosting.md).

## Konfiguration im Host

| Variable | Wert |
| --- | --- |
| `APP_NAME` | `Cash Machine` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Einmalig lokal mit `php artisan key:generate --show` erzeugen, als Secret speichern |
| `APP_URL` | Die endgültige `https://…koyeb.app`-Adresse |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `de` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Neon-Verbindungs-URL mit `sslmode=require`; als Secret speichern |
| `SESSION_DRIVER` / `CACHE_STORE` | `database` |
| `SESSION_SECURE_COOKIE` / `SESSION_HTTP_ONLY` | `true` |
| `SESSION_SAME_SITE` | `lax` |
| `PUBLIC_DEMO_ENABLED` | `true` ausschließlich für diese Demo-Datenbank |
| `SECURITY_HEADERS_ENABLED` | `true` |
| `TRUSTED_PROXIES` | Bestätigte Proxy-IP/CIDR-Liste des Zielhosts |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stderr` / `warning` |
| `QUEUE_CONNECTION` | `sync` (keine Hintergrundjobs vorgesehen) |

`TRUSTED_PROXIES=*` ist nur vertretbar, wenn die Instanz ausschließlich durch den verwalteten Gateway erreichbar ist und dieser Forwarded-Header zuverlässig ersetzt. Diese Voraussetzung am Zielhost bestätigen. Ohne bestätigtes Proxy-Verhalten ist HTTPS-Erkennung einschließlich HSTS und IP-basierter Limits ungesichert. Laravels TrustProxies-Middleware liest die Konfiguration auch nach `config:cache`.

Der `APP_KEY` bleibt über Neustarts und Deployments unverändert. `composer setup` gehört nicht in das Deployment: Es regeneriert den Schlüssel. Keine `.env`, lokalen SQLite-Dateien, Sitzungen oder lokalen Betreiberpasswörter werden ins Docker-Image kopiert.

## Erste Veröffentlichung

1. Das separate Neon-Projekt ist angelegt und sein Branch `production` verknüpft. Für Koyeb die direkte Verbindungs-URL mit TLS verwenden; sie wird erst dort als Secret hinterlegt. Die lokale Entwicklungsdatenbank bleibt getrennt.
2. In Koyeb das öffentliche GitHub-Repository verbinden, Branch `main`, Build mit dem enthaltenen `Dockerfile`, Webdienst ausdrücklich **Free**, Frankfurt, Port **8080**, HTTP-Healthcheck **`/up`** wählen. Kein Volume und keinen zweiten Dienst hinzufügen.
3. Die Variablen und Secrets oben setzen. Die endgültige Service-Adresse als `APP_URL` hinterlegen.
4. Nur einen grünen CI-Stand deployen. Vorläufig automatisches Deployment deaktivieren, damit fehlgeschlagene CI-Läufe nicht veröffentlicht werden. Rollbackfähigen Commit notieren.
5. Beim Containerstart prüft `atm:deployment-check` Konfiguration und Datenbankverbindung. Danach folgen Config-Cache, additive Migrationen, idempotente Einrichtung der beiden Demo-Karten/ATM und Route-/View-Cache. Bei einem Fehler startet Apache nicht. Es wird **kein bekannter Betreiber** in Produktion angelegt.
6. Falls der Betreiberbereich vorgeführt werden soll, auf einem vertrauenswürdigen lokalen Checkout mit derselben Produktionsdatenbank `php artisan atm:operator-create deine-adresse@example.org` ausführen. Das Passwort wird verdeckt abgefragt, ist mindestens 16 Zeichen lang und steht weder im Repository noch in Prozessargumenten. Dafür die Produktionsvariablen nur in einer separaten, ignorierten Umgebung verwenden; keine lokalen Datenbankwerte überschreiben.

Das Image verwendet PHP 8.4/Apache, Node 24 nur während des Builds und maximal zwei Apache-Worker. Speicherverbrauch und Startzeit auf der kleinen Free-Instanz sind noch nicht gemessen. Die Basisimages folgen ihren gepflegten Hauptversionen; PHP-/OS-Patchstände sind damit bewusst nicht auf einen unveränderlichen Digest festgeschrieben. Abhängigkeiten werden über beide Lockfiles fixiert.

## Demo-Reset und Aufbewahrung

`PUBLIC_DEMO_ENABLED=false` ist lokal der Standard. Bei aktivierter öffentlicher Demo richtet `atm:demo-provision` ausschließlich DEMO-001, DEMO-002 und BER-DEMO-01 ein; es setzt bestehende Salden nicht zurück. Alle Konten starten bei 0 €; Besucher beginnen mit einer Einzahlung.

24 Stunden nach dem letzten Reset setzt die nächste Anfrage an `/atm` oder `/atm/*` die bekannten Demo-Konten auf 0 €, löscht deren fiktive Buchungen und zugeordnete Audit-Ereignisse, hebt Kartensperren auf und stellt den Standard-Scheinbestand und aktiven Automatenstatus wieder her. Ein Versionszähler macht alte Kartensitzungen und vorbereitete Buchungsanfragen ungültig. Andere Konten, Benutzer und deren Buchungen bleiben erhalten. Audit-Ereignisse älter als sieben Tage werden im ausdrücklich aktivierten Demo-Modus ebenfalls verworfen. Das ist eine bewusst kurzlebige Simulation, kein revisionssicheres Archiv.

Der Reset arbeitet atomar und wird durch eine gesperrte Datenbankzeile auch bei parallelen Anfragen nur einmal ausgeführt. Er benötigt keinen ständig laufenden Scheduler: Nach einer Ruhephase erfolgt er beim nächsten Besucher. Ein erzwungener Reset ist ausschließlich per CLI möglich:

```sh
php artisan atm:demo-reset --force
php artisan atm:demo-reset --force --if-due
```

Diese Befehle löschen fiktive Daten. Sie gehören ausschließlich in die dedizierte Showcase-Umgebung. Es gibt keine öffentliche Reset-Route. IP-basierte Limits begrenzen Geldbewegungsanfragen auf 30 pro Minute zusätzlich zu den PIN-Limits; sie verhindern weder verteilten Missbrauch noch das vorübergehende Leeren gemeinsam genutzter Konten. Eine absolute Zeilen-/Traffic-Obergrenze ist nicht implementiert. Anbieterquoten vor Freigabe überwachen.

## Abnahme und Wiederherstellung

- HTTPS öffnet ohne Zertifikatsfehler; Assets werden ohne CSP-Fehler geladen.
- Login, Einzahlung mit Zweck, Auszahlung, beide Belege, Filter/Sortierung und Abmeldung funktionieren an der öffentlichen Adresse.
- Session-Cookie ist Secure/HttpOnly/SameSite=Lax; HSTS, CSP und Basisheader sind vorhanden. Proxy-Header einer direkten Besucheranfrage dürfen die ermittelte IP nicht beliebig überschreiben.
- Bekannte lokale Betreiberzugänge sind öffentlich ungültig. `/operator` ist geschützt; Fehlerseiten zeigen keine Debug-Daten.
- Erzwungener Demo-Reset invalidiert eine offene Sitzung und stellt Saldo/Scheine wieder her; ein normaler Neustart erhält Daten und `APP_KEY`.
- Aufwachen nach Ruhephase, Speicherverbrauch, Datenbankverbindungen und Free-Quoten tatsächlich messen. `/up` bestätigt den Webprozess; es ist kein vollständiger Datenbank-/Buchungsmonitor. Keine dauernden externen Keepalive-Abfragen voraussetzen.

Für ein Code-Rollback den letzten grünen Commit mit unveränderten Secrets deployen. Bei einer Migration zuerst deren Kompatibilität prüfen; das automatische Zurückrollen oder Löschen von Migrationen ist kein Wiederherstellungsweg. Eine kaputte fiktive Demo kann auf einer **neuen dedizierten** Neon-Datenbank migriert/provisioniert werden; URL als Secret aktualisieren. Es werden keine echten Geschäftsdaten aufbewahrt. Wer Demo-Verläufe erhalten möchte, muss vor dem Reset getrennt sichern; derzeit ist kein bezahlter Backupdienst vorgesehen.

## CI und lokale Containerprüfung

GitHub Actions prüft PHP 8.4, Node 24, SQLite-Tests, fünf reale PostgreSQL-Mehrprozessszenarien, Pint, TypeScript/Build und den Browserablauf in Chromium. Ein zweiter Job baut das Docker-Image und startet es gegen einen isolierten PostgreSQL-Service mit Produktionskonfiguration. Ein grüner Containerjob bestätigt Installation und HTTP-Start, aber keine Koyeb-/Neon-Eigenschaften.

Lokal lässt sich das Image mit `docker build -t atm-showcase .` bauen. Der Docker-Daemon muss laufen. Der Containerstart benötigt die oben genannten Produktionsvariablen und eine ausschließlich fiktive PostgreSQL-Datenbank. Der Laravel-Entwicklungsserver bleibt für die normale SQLite-Vorführung vorgesehen.

Referenzen: [Koyeb Docker](https://www.koyeb.com/docs/build-and-deploy/deploy-with-git), [Free-Instanzgrenzen](https://www.koyeb.com/docs/reference/instances), [Neon Free](https://neon.com/faqs/free-plan-limits-and-quotas), [Laravel-Docker-Beispiel bei Render](https://render.com/docs/deploy-php-laravel-docker), [PHP CI](https://github.com/shivammathur/setup-php), [Node CI](https://github.com/actions/setup-node).
