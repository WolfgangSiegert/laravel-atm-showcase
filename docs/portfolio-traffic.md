# Showcase-Traffic

## Öffentliche Client-Verträge

Die statischen Portfolio-Seiten senden `version=1`, `site=portfolio` und einen
allowlist-validierten Pfad als Formular-POST an
`https://atm.tiny-bits.org/api/portfolio-traffic`. Zulässige Pfade sind `/`,
`/portfolio/`, `/portfolio/en/`, `/portfolio-vue/` und `/portfolio-vue/en/`;
zulässige Origins `https://tiny-bits.org`, `https://www.tiny-bits.org` und
`https://wolfgangsiegert.github.io`. Dieses bestehende Verhalten bleibt
unverändert.

Der JoinSplit-Client sendet mit `mode: no-cors`, `credentials: omit`,
`referrerPolicy: no-referrer` und `keepalive: true` an
`POST https://atm.tiny-bits.org/api/showcase-traffic`. Der Endpoint akzeptiert
ausschließlich:

- `version=1`
- `site=joinsplit`
- `path=/app`
- Origin exakt `https://joinsplit.tiny-bits.org`

Beide Clients verwenden
`application/x-www-form-urlencoded;charset=UTF-8`. Fehlende oder fremde
JoinSplit-Origins liefern 403, ungültige Payloads 422 und deaktivierte
Erfassung 404. Akzeptierte Requests und offensichtliche Bots erhalten 204 ohne
Cookie. Es gibt kein Client-Secret. Der Origin-Header ist eine
Missbrauchshürde, aber kein kryptografischer Herkunftsnachweis.

## Speicherung und Aufbewahrung

`portfolio_traffic_events` enthält weiterhin einzelne Ereignisse mit
serverseitigem UTC-Zeitpunkt, fester Site, freigegebenem Pfad und einem
HMAC-SHA-256. Der HMAC kombiniert die von Laravel ermittelte Client-IP mit
einer groben Browser-/Plattformklasse und dem stabilen `APP_KEY`. Rohe
IP-Adresse, vollständiger User-Agent, Cookies, Query-Parameter und aufrufende
URL werden nicht gespeichert. Diese pseudonymen Kennungen ermöglichen
Kennzahlen für frei wählbare Zeiträume, sind aber keine verlässlichen
Personenzahlen.

JoinSplit wird getrennt und ausschließlich in `showcase_traffic_daily`
aggregiert. Eine Zeile enthält nur `visit_date`, `site`, `path` und
`view_count`. Datum und Zähler werden für `Europe/Berlin` atomar per
PostgreSQL-/SQLite-Upsert geführt. Die Tabelle enthält absichtlich keine
IP-Adresse, keinen IP-Hash, User-Agent, Browser-/Plattformklasse,
Besucher-HMAC, Cookie, Referrer, Query-Parameter, konkrete JoinSplit-Route oder
Account-, Gruppen- und Personendaten. Zeitstempel sind nicht erforderlich und
werden ebenfalls nicht gespeichert.

Beide Modelle verwenden `PORTFOLIO_TRAFFIC_RETENTION_DAYS`, standardmäßig 90
Tage. Bei einem akzeptierten Request löscht der jeweilige Dienst abgelaufene
Zeilen opportunistisch und idempotent. Ohne neuen Traffic kann die physische
Löschung entsprechend später stattfinden; ein Scheduler ist nicht nötig.

## Rate Limit und Botfilter

Portfolio behält sein IP-basiertes, per HMAC geschütztes Rate-Limit. JoinSplit
verwendet dagegen einen gemeinsamen, kurzlebigen Schlüssel aus der festen Site
und dem erlaubten Origin. Es entsteht keine besucherbezogene Kennung.
Missbrauch kann allerdings das gemeinsame Minutenlimit ausschöpfen und dadurch
legitime JoinSplit-Clients bis zum Ablauf des Fensters mit ausschließen.

Der grobe Botfilter liest den User-Agent nur während der Anfrage. Offensichtliche
Bots werden mit 204 beantwortet, ohne den Tageszähler zu erhöhen; der
User-Agent wird nicht in der JoinSplit-Statistik persistiert.

## Private Auswertung

Nur authentifizierte Superadmins mit `canManageAdministration()` erreichen
`/admin/portfolio-traffic`. Die bestehende URL bleibt gültig; die Oberfläche
heißt nun „Showcase-Traffic“. Nicht angemeldete Nutzer werden zur Anmeldung
geleitet, normale Nutzer und öffentliche Viewer erhalten 403 und keine
versteckten Inertia-Props.

Die Projektauswahl bietet Portfolio, JoinSplit und eine Gesamtübersicht.
Portfolio behält Aufrufe, unterschiedliche Kennungen, zusätzliche Aufrufe,
wiederkehrende Kennungen, Tagesverlauf und Pfadverteilung. JoinSplit zeigt nur
App-Aufrufe, Tagesverlauf, Quelle `JoinSplit` und den Bereich `/app`; sichtbar
wird erklärt, dass dies anonyme aggregierte Aufrufe und keine eindeutigen
Menschen sind. Die Gesamtübersicht addiert ausschließlich Aufrufe und enthält
keine projektübergreifenden Unique- oder Returning-Kennzahlen.

Datumsgrenzen und JoinSplit-Berichtstage verwenden `Europe/Berlin`. Beim
Portfolio begrenzen gemeinsame IP-Adressen sowie IP-, Browser- und
Plattformwechsel weiterhin die Aussagekraft der pseudonymen Kennungen.

## Konfiguration

| Variable | Lokal | Render | Bedeutung |
| --- | --- | --- | --- |
| `PORTFOLIO_TRAFFIC_ENABLED` | `false` | `true` | Bestehenden Portfolio-Endpoint aktivieren |
| `PORTFOLIO_TRAFFIC_RETENTION_DAYS` | `90` | `90` | Gemeinsame Aufbewahrungsfrist beider Modelle |
| `PORTFOLIO_TRAFFIC_RATE_LIMIT` | `60` | `60` | Portfolio-Requests pro ermittelter IP und Minute |
| `SHOWCASE_TRAFFIC_ENABLED` | `false` | `true` | Zentralen JoinSplit-Endpoint aktivieren |
| `SHOWCASE_TRAFFIC_JOINSPLIT_ORIGIN` | `https://joinsplit.tiny-bits.org` | identisch | Exakter erlaubter Origin |
| `SHOWCASE_TRAFFIC_JOINSPLIT_PATH` | `/app` | `/app` | Einzige erlaubte JoinSplit-Kategorie |
| `SHOWCASE_TRAFFIC_RATE_LIMIT` | `120` | `120` | Gemeinsame Requests pro Minute; nicht besucherbezogen |

Vor Veröffentlichung des JoinSplit-Clients müssen zuerst die additive
Migration und Render-Konfiguration ausgerollt werden. Danach sind im Zielsystem
204 mit erlaubtem Origin, 403 mit fremdem Origin, die Zählererhöhung und die
private Superadmin-Auswertung zu prüfen. Erst anschließend sollte der externe
Client aktiviert werden.
