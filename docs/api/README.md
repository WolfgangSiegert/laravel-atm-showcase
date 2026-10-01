# HTTP-Schnittstelle und Postman

Die Anwendung stellt aktuell **keine eigenständige JSON-API** bereit. Die in
[`openapi.json`](openapi.json) beschriebene Schnittstelle ist der tatsächlich
vorhandene, sitzungsbasierte Laravel-/Inertia-HTTP-Vertrag. Erfolgreiche
Schreibzugriffe antworten in der Regel mit einem Redirect und nicht mit einem
JSON-Dokument.

Die OpenAPI-Datei ist die verbindliche maschinenlesbare Quelle für Pfade,
Methoden und Eingabefelder. Die Routentabelle im Haupt-README bleibt nur eine
kompakte Übersicht.

## In Postman importieren

1. **Import** → **Files** wählen und `docs/api/openapi.json` importieren.
2. Die Variable `baseUrl` auf `http://127.0.0.1:8000` oder den gewünschten Host
   setzen.
3. Cookies für den Host in Postman aktiviert lassen.
4. Vor einem schreibenden Request zunächst `GET /atm/cards` beziehungsweise
   `GET /admin/login` senden. Laravel setzt dabei `XSRF-TOKEN` und das
   Sitzungscookie.
5. Den URL-dekodierten Wert des Cookies `XSRF-TOKEN` als Header
   `X-XSRF-TOKEN` des schreibenden Requests mitsenden.
6. Redirects während der Fehlersuche optional deaktivieren. Dadurch bleibt die
   ursprüngliche `302`-/`303`-Antwort samt `Location`-Header sichtbar.

Für ATM-Buchungen muss außerdem erst über `POST /atm/session` eine gültige
Kartensitzung aufgebaut werden. Admin-Schreibzugriffe benötigen eine
Superadmin-Sitzung; der optionale Gastzugang ist absichtlich nur lesend.

## Fehlerantworten

Mit `Accept: application/json` liefert Laravel Validierungsfehler als JSON mit
Status `422`. Erfolgreiche Antworten bleiben dennoch Redirects, da die
Controller den Browser- und keinen JSON-API-Vertrag implementieren. Ohne diesen
Accept-Header werden Validierungsfehler über die Sitzung zurück zur jeweiligen
Seite geleitet.

Die Dokumentation enthält absichtlich keine stabilen Antwortschemas für
Inertia-Seiten. Deren Props sind Teil der Weboberfläche und kein zugesagter
externer API-Vertrag.

## Prüfung und Pflege

Der Test `tests/Feature/OpenApiDocumentationTest.php` prüft, dass die Datei
gültiges JSON ist, Operation-IDs eindeutig sind und alle dokumentierten
kanonischen Routen weiterhin zur Laravel-Routentabelle passen.

```sh
php artisan test tests/Feature/OpenApiDocumentationTest.php
```

Wenn ein Endpoint oder Eingabefeld geändert wird, sollen Route beziehungsweise
FormRequest, zugehöriger Feature-Test und `openapi.json` gemeinsam angepasst
werden.
