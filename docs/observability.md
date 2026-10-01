# Logging und Fehlerdiagnose

Die Anwendung trennt fachliche Audit-Ereignisse von technischen Logs. `audit_events`
dokumentiert fachlich relevante Vorgänge; die Laravel-Logs dienen ausschließlich der
Betriebsdiagnose.

## Traffic-Logging

`TRAFFIC_LOG_ENABLED=true` schreibt je Anfrage ein strukturiertes Ereignis in den
Channel `TRAFFIC_LOG_CHANNEL` (standardmäßig täglich rotierend nach
`storage/logs/traffic-*.log`). Erfasst werden Request-ID, HTTP-Methode, benannte Route
und Routenmuster, Authentifizierungsstatus, Statuscode, Laufzeit und Fehlerstatus.
Die Request-ID wird von der Anwendung erzeugt, im Header `X-Request-ID` zurückgegeben
und kann einer Fehlermeldung zugeordnet werden.

Bewusst **nicht** erfasst werden Roh-URL, Querywerte, Routenparameter, Request- und
Response-Body, IP-Adresse, Benutzer-ID, Cookies und Session-ID. Die Header-Allowlist
`TRAFFIC_LOG_HEADERS` enthält standardmäßig nur `Accept` und `Content-Type`. Falls ein
sensitiver Header versehentlich zur Allowlist ergänzt wird, erscheint nur
`[REDACTED]`. `TRAFFIC_LOG_EXCEPT` nimmt kommaseparierte Pfadmuster aus, standardmäßig
den Healthcheck `/up`.

`TRAFFIC_LOG_DAYS` begrenzt die lokale Aufbewahrung standardmäßig auf 14 Dateien. Das
ist keine Garantie für nachgelagerte Plattform-Logs; deren Löschfristen müssen beim
Hosting separat festgelegt werden.

Das Render-Deployment verwendet den separaten Channel `traffic_stderr` mit Level
`info`, weil das Container-Dateisystem weder dauerhafte Aufbewahrung noch einen
sinnvollen Logzugriff garantiert. Der allgemeine `stderr`-Channel bleibt dadurch auf
`LOG_LEVEL=warning`, ohne die Traffic-Ereignisse herauszufiltern. Die tatsächliche
Retention bestimmt weiterhin die Plattform.

## Fehler-Reporting

Unbehandelte, von Laravel reportbare Exceptions erhalten denselben datensparsamen
Request-Kontext. Ohne weitere Konfiguration nutzt die Anwendung den mit `LOG_CHANNEL`
gewählten Channel. `ERROR_REPORT_CHANNEL` kann auf einen anderen bereits
konfigurierten Channel wie `stderr` zeigen; dann wird der Fehler ausschließlich
dorthin geroutet. Externe Dienste werden nicht automatisch aktiviert und
Zugangsdaten gehören ausschließlich in den Secret-Speicher der Hostingumgebung.

Für Produktion gelten mindestens:

- `APP_DEBUG=false` und `LOG_LEVEL=warning` für das allgemeine Anwendungslog;
- einen zur Plattform passenden `ERROR_REPORT_CHANNEL` wählen oder leer lassen;
- Aufbewahrung und Zugriffsrechte der Plattform-Logs prüfen;
- keine sensitiven Header zur Allowlist hinzufügen.

Traffic-Logs sind eine diagnostische Basis, keine vollständige Metrik- oder
Tracing-Lösung. Ohne IP- und Benutzerkennung lassen sich absichtlich keine
personenbezogenen Nutzungsprofile oder Zahlen unterschiedlicher Personen ableiten.

Die persistenten Tageszähler in `usage_metrics` sind davon getrennt. Sie dienen einer langfristigen, cookielosen Nutzungsübersicht, werden nicht in den Traffic-Logkanal geschrieben und enthalten keine Request- oder Browserkennung. Aktivierung, Zählregeln und datenschutzrechtliche Grenzen stehen in [deployment.md](deployment.md#cookielose-nutzungsstatistik).
