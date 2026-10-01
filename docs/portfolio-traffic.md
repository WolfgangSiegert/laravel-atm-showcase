# Portfolio-Traffic

## Öffentlicher Client-Vertrag

Die statischen Portfolio-Seiten senden einen zustandslosen Request an
`POST https://atm.tiny-bits.org/api/portfolio-traffic` mit dem Content-Type
`application/x-www-form-urlencoded;charset=UTF-8`:

```text
version=1&site=portfolio&path=%2Fportfolio%2F
```

`path` muss exakt `/`, `/portfolio/`, `/portfolio/en/`, `/portfolio-vue/` oder
`/portfolio-vue/en/` sein. Zulässige Origins sind `https://tiny-bits.org`,
`https://www.tiny-bits.org` und `https://wolfgangsiegert.github.io`.
Erfolgreiche oder als Bot erkannte Requests antworten mit `204 No Content`.

Der Browser-Client verwendet `mode: no-cors`, `credentials: omit` und
`keepalive: true` und liest die opaque Antwort nicht. Es gibt absichtlich kein
Client-Secret. Der Origin-Header kann außerhalb eines Browsers gefälscht werden;
Origin-Allowlist und IP-Rate-Limit sind daher Missbrauchshürden, kein
kryptografischer Herkunftsnachweis.

## Speicherung und Aufbewahrung

`portfolio_traffic_events` enthält nur:

- `occurred_at`: serverseitiger UTC-Zeitpunkt,
- `site`: derzeit fest `portfolio`,
- `path`: einer der freigegebenen Pfade,
- `visitor_hash`: HMAC-SHA-256.

Der HMAC verwendet den stabilen `APP_KEY` und kombiniert die von Laravel
ermittelte Client-IP mit einer groben Browser-/Plattformklasse. Die Anwendung
speichert weder rohe IP-Adresse noch vollständigen User-Agent, Cookies,
Query-Parameter oder die URL des aufrufenden Dokuments. Ein Wechsel von
`APP_KEY` ändert alle zukünftigen Kennungen und unterbricht Vergleiche mit
älteren Ereignissen.

Bei jedem akzeptierten Request löscht die Anwendung Ereignisse, die älter als
`PORTFOLIO_TRAFFIC_RETENTION_DAYS` sind; Standard und Render-Wert sind 90 Tage.
Die Löschung ist idempotent und benötigt keinen Scheduler. Wenn keine neuen
Requests eintreffen, findet die physische Bereinigung erst beim nächsten
akzeptierten Aufruf statt.

## Private Auswertung

Nur ein authentifizierter `superadmin` mit `canManageAdministration()` erreicht
`/admin/portfolio-traffic`. Nicht angemeldete Nutzer werden zur Anmeldung
geleitet; normale Nutzer und der öffentliche Viewer erhalten 403. Das normale
Admin-Dashboard enthält keine versteckten Portfolio-Props.

Der Zeitraum ist innerhalb der Aufbewahrungsfrist frei wählbar. Seine
Datumsgrenzen werden in `Europe/Berlin` interpretiert und für die Datenbank nach
UTC umgerechnet. Angezeigt werden Gesamtaufrufe, unterschiedliche HMAC-Kennungen,
zusätzliche Aufrufe, bereits vor dem Zeitraum gesehene Kennungen, Tagesverlauf
und Pfadverteilung.

Die Kennungen sind keine verlässlichen Personenzahlen. Gemeinsame öffentliche
IP-Adressen können mehrere Personen zusammenfassen; IP-, Browser- oder
Plattformwechsel können dieselbe Person mehrfach erscheinen lassen. Botfilter,
Origin-Allowlist und Rate-Limit reduzieren Verzerrungen, verhindern absichtlich
gefälschte Ereignisse aber nicht vollständig.

## Konfiguration

| Variable | Lokal | Render | Bedeutung |
| --- | --- | --- | --- |
| `PORTFOLIO_TRAFFIC_ENABLED` | `false` | `true` | Endpoint aktivieren |
| `PORTFOLIO_TRAFFIC_RETENTION_DAYS` | `90` | `90` | Aufbewahrungsfrist |
| `PORTFOLIO_TRAFFIC_RATE_LIMIT` | `60` | `60` | Requests pro ermittelter IP und Minute |

Die Migration ist additiv und für SQLite sowie PostgreSQL ausgelegt. Vor dem
Veröffentlichen des statischen Clients müssen Migration, 204-Test mit erlaubtem
Origin, 403-Test mit fremdem Origin und die private Superadmin-Seite im Zielsystem
geprüft sein.
