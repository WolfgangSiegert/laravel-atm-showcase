# Entscheidungen, Annahmen und offene Fragen

Stand: v0.9, 11. September 2026. Der Abschnitt v0.1 beschreibt die ursprüngliche Ausgangslage.

## In v0.1 festgelegt

| Entscheidung | Grund / Konsequenz |
| --- | --- |
| Lokales Laravel-Projekt in `/Users/Wolfgang/developer/projects/LaravelCashMashine` | Der Arbeitsbereich wurde während der Umsetzung gewechselt. Der zuvor erzeugte Stand im ChatGPT-Projektordner wurde kopiert; der hier liegende Stand ist maßgeblich. |
| PHP 8.4.x, Node 24.x | Expliziter PHP-Wunsch; vorhandenes Node 18 wurde für den aktuellen Vite-Stack nicht verwendet. `.php-version`, `.nvmrc` und Paketanforderungen dokumentieren das. |
| Composer-Plattform PHP 8.4.0 | Verhindert, dass eine Installation unter globalem PHP 8.5 Abhängigkeiten auswählt, die mindestens 8.5 erfordern. Zusätzlich wurden die Prüfungen mit echtem PHP 8.4 ausgeführt. |
| Laravel-Basisskelett plus manuelle Inertia-/Vue-Integration | Ein Auth-Starter-Kit würde mehr Anmeldefunktionen und Komponenten einführen als für v0.1 nötig. |
| Inertia 3 auf Server und Client | Eine gemeinsame Hauptversion; beide konkreten Paketversionen stehen in den Lockfiles. Manuelle Vue-Initialisierung macht den Einstieg nachvollziehbar. |
| Eine Landingpage `/atm`, `/` leitet dorthin | Kein zweiter Router oder unnötige Controller-Abstraktion. |
| Tailwind 4, lokale Systemschrift, kleines eigenes SVG | Keine Schrift-CDNs oder externen Bilddienste nötig. |
| Nur technische Laravel-Tabellen | `users`, Passwort-Reset-Tokens, Sessions, Cache und Jobs sind Grundgerüst. Keine Customer-/Account-/Card-/Transaction-/ATM-/CashInventory-Tabellen. Kein Login-Endpunkt, keine Benutzer-Seed-Daten. |
| Dateibasierte Standardkonfiguration, SQLite lokal | Einfacher Start ohne Serverdienst. Sessions/Cache/Jobs behalten die Laravel-Datenbanktreiber; kein Queue-Worker nötig, da keine Jobs implementiert sind. |
| Deutsch als Oberfläche, UTC im Backend | Zeitzonenformatierung und vollständige Übersetzungen erst mit zeitabhängigen Funktionen. |
| Keine Veröffentlichung / kein Remote-Repository | Angefordert ist ein lokales Grundsetup. CI und Deployment sind spätere Entscheidungen. |

## Vorläufige fachliche Annahmen

EUR, Integer-Centbeträge, ein Demo-ATM, kein Dispo, genau ein Kontoinhaber je Konto und genau ein Konto je Karte. Diese Annahmen dienen dem dokumentierten Modell; sie werden in v0.1 nicht durch Geschäftslogik erzwungen. Der ursprüngliche Arbeitsname **Cash Machine** wurde später durch **LERN-Bank Mein Geldautomat** ersetzt.

Die Planung orientiert sich bewusst am kleinen Lernumfang. Das kann spätere Erweiterungen wie Gemeinschaftskonten, mehrere Währungen oder echte Ledger-Strukturen erschweren; deshalb werden diese Grenzen explizit festgehalten statt vermeintlich zukunftssicher abstrahiert.

## Noch offen, vor den jeweiligen Features zu entscheiden

| Thema | Entscheidungspunkt |
| --- | --- |
| Identität / Anmeldung | v0.2 verwendet eine unabhängige Card-/PIN-Sitzung. Zusätzlicher Customer-/User-Login bleibt außerhalb des Umfangs. |
| PIN-Schutz | Vorläufige v0.2-Werte siehe unten. Administrativer Rücksetzweg bleibt offen. |
| Karten und Konten | Kartengültigkeit, Sperrgründe, Seed-Konten und Anfangssalden. |
| Buchungsmodell | Einfacher Saldo mit unveränderlicher Historie oder echtes Ledger; Behandlung des Anfangssaldos und Gegenbuchungen. |
| Bargeld | Erlaubte Scheine, Auswahlalgorithmus, Höchstbetrag pro Vorgang/Tag und Verhalten bei knappem Bestand. |
| Einzahlung | Freier Betrag oder konkrete Scheine? Werden eingezahlte Scheine unmittelbar auszahlbar? |
| Datenbank | PostgreSQL für Showcase wahrscheinlich; noch kein verbindliches Deploymentziel. Nebenläufigkeit dort gesondert prüfen. |
| Audit | Aufbewahrungsdauer, Archivierung und Löschweg für eine öffentliche Instanz. |
| Qualität / Betrieb | Browser-Testautomatisierung, CI-Anbieter, Deployment, Lizenzentscheidung für eigene Projektanteile. |

## Umgebung und Nebenwirkungen

PHP 8.4.25 wurde über Homebrew separat installiert, ohne globale PHP-Verknüpfung oder Shell-Konfiguration umzuschalten. Homebrew führte dabei automatisch seine übliche Bereinigung alter Pakete/Caches aus; dies war keine für das Projekt notwendige Änderung. Node 24.20.0 war bereits installiert und wurde nur pro Prozess ausgewählt. Eine vorhandene npm-Konfigurationswarnung zu `//prefix` wurde nicht durch Änderungen an der globalen npm-Konfiguration behoben.

Das offizielle Skelett brachte eine Aufforderung zur Boost-Installation mit. Nach der vom Nutzer ersetzten AGENTS-Anweisung wurde diese zusätzliche Integration wieder entfernt; v0.1 benötigt sie nicht. Die projektspezifische AGENTS.md dokumentiert nur die gewählten Laufzeiten, den Umfang und die Prüfungen.


## v0.2: explizit gewählte Annahmen

Mit der Fortsetzung nach v0.1 wurde der nächste angekündigte Meilenstein Demo-Karten/PIN-Sitzung begonnen. Mangels abweichender Vorgaben wurden folgende konfigurierbare Lernprojekt-Defaults verwendet:

- Vierstellige ASCII-PIN als String, einschließlich führender Nullen. Hashing über Laravels `hashed`-Cast und Prüfung mit `Hash::check`.
- Fünf falsche PINs sperren eine Karte 900 Sekunden. Fehlversuche persistieren in der Datenbank; nach Sperrablauf beginnt ein neuer Versuchszähler. Richtige Anmeldung setzt ihn zurück.
- Zehn Anmeldeanfragen pro IP und Minute, einschließlich ungültiger Eingaben und erfolgreicher Anmeldungen. Das gemeinsame IP-Limit kann Nutzer hinter derselben Adresse betreffen.
- 300 Sekunden ohne geschützte Serveranfrage beenden die Sitzung. Maßgeblich ist die Serverzeit; der Frontend-Timer dient nur der zeitnahen Darstellung und kann in Hintergrund-Tabs verzögert werden.
- Erfolgreiche PIN-Prüfung erneuert die Session-ID. Abmeldung und ungültige/abgelaufene Sitzung invalidieren die Session und erneuern den CSRF-Token. PIN wird von Laravel-Fehlerweiterleitungen (`dontFlash`) ausgeschlossen.
- Die öffentliche Auswahl enthält nur Karten-ID und Demo-Referenz. Geschützte Antworten wählen Felder explizit aus. Kein Kontostand in v0.2. Inertia-History wird verschlüsselt und bei An-/Abmeldung bereinigt; geschützte Antworten sind `no-store`.
- Zwei fiktive Kunden/Konten/Karten, Anfangssaldo 0 EUR, keine festgelegte Kartengültigkeit (`expires_at = null` im Seed). Kartengültigkeit und Account-/Card-Status werden dennoch bei jedem geschützten Request geprüft.
- Wiederholtes Seeding setzt keine PINs, Sperren oder Salden zurück. Demo-Seeding ist außerhalb `local`/`testing` blockiert.

### Verbleibende Grenzen

Die öffentlich bekannten Demo-PINs bieten keine reale Kontosicherheit. SQLite plus Transaktion/Retry ist keine bestätigte PostgreSQL-Nebenläufigkeitslösung: `lockForUpdate` muss mit der späteren Ziel-Datenbank gesondert getestet werden. Auch das cachegestützte IP-Limit ist keine verteilte Abuse-Abwehr. Geldbetrags-Constraints, Ledger, Tageslimits und Bargeldregeln werden erst mit den entsprechenden Features umgesetzt. Die History-Verschlüsselung benötigt einen sicheren Browserkontext (lokal Loopback; bei späterem Hosting HTTPS). Ein vollständiges Sicherheits- oder Accessibility-Audit ist nicht erfolgt.


## v0.3: Kontoübersicht und simulierte Einzahlung

- Zunächst einfacher Integer-Centsaldo plus anwendungsseitig unveränderliche Historie, kein Double-Entry-Ledger. Die Einzahlungs-Action ist die einzige neue Schreibstelle; sie schreibt Saldo und Transaction gemeinsam in einer Datenbanktransaktion.
- Einzahlung als frei eingegebener Eurobetrag ohne Scheine: 0,01 bis 10.000,00 EUR, maximal 10.000.000,00 EUR Gesamtsaldo. Diese vorläufigen Demo-Grenzen sind konfigurierbar. Keine Float-Arithmetik für die Buchung; Zahlenformatierung im Browser dient nur der Anzeige.
- Die Karte und das Konto werden aus der geprüften Sitzung abgeleitet und innerhalb der Buchung erneut geprüft. Übergebene Konto-/Karten-IDs können das Ziel nicht ändern.
- UUID-Anfrageschlüssel, Unique-Constraint je Konto und Schlüssel. Wiederholungen mit identischer Karte und identischem Betrag liefern die vorhandene Buchung; abweichende Daten werden abgewiesen. Ein neuer Schlüssel ist eine neue Buchungsabsicht.
- Historie ausschließlich für das Sitzungskonto, neueste Einträge zuerst, zehn pro Seite. Datumsanzeige Europe/Berlin, Speicherung nach Laravel-Konvention UTC.
- Vorhandene positive Kontosalden werden beim Schema-Upgrade als `opening` dokumentiert. Neue Demo-Konten starten weiterhin bei null. Keine implizite Guthabenvergabe durch Seeding.
- Modell-Events verhindern Bearbeiten/Löschen einer Transaction über die Eloquent-Instanz. Direkte SQL-/Query-Builder-Zugriffe können dies umgehen; es gibt keine entsprechende Web-Route. Vollständige Revisionssicherheit ist nicht implementiert.
- Im Browser wurde DEMO-002 mit 25,50 EUR Demo-Guthaben bebucht. Der Eintrag bleibt als sichtbarer Testbeleg erhalten.

Noch keine Aussage über physische Geldbewegungen, Bankbuchhaltung oder unter Parallelzugriff bewiesene PostgreSQL-Semantik. Die Datenbanktransaktion und Unique-Constraint schützen das lokale Modell; parallele Lasttests bleiben gesondert nötig.

## v0.4: Bargeldbestand und simulierte Auszahlung

- Ein aktiver EUR-Demo-Automat (`BER-DEMO-01`) wird mit 10-, 20-, 50- und 100-Euro-Scheinen angelegt. Der Anfangsbestand liegt in `config/atm.php`; wiederholtes Seeding setzt einen bereits veränderten Bestand nicht zurück.
- Auszahlungen erlauben ausschließlich ganze Euro von 10,00 bis 1.000,00 EUR. Es gibt weiterhin kein Tageslimit. Der Sitzungskontext bestimmt Karte, Konto und den konfigurierten Automaten; vom Browser übermittelte Fremd-IDs werden ignoriert.
- Der Auswahlalgorithmus berücksichtigt begrenzte Bestände und minimiert die Anzahl ausgegebener Scheine. Damit scheitert er nicht an den bekannten Greedy-Gegenbeispielen. Eine Produktregel für die bevorzugte Kombination bei gleicher Scheinzahl ist noch nicht festgelegt.
- Account, ATM und Bestandszeilen werden innerhalb einer Datenbanktransaktion gesperrt. Saldo, Scheinmengen und Transaction werden atomar geändert. Die tatsächliche Wirkung von `lockForUpdate` unter PostgreSQL ist weiterhin separat zu prüfen; SQLite-Tests beweisen diese Semantik nicht.
- Erfolgreiche Auszahlungen speichern ihre Scheinverteilung als JSON-Snapshot in der Transaction. Spätere Bestandsänderungen verändern dadurch die Historie nicht. Einzahlungen bleiben ohne Bargeldwirkung und ATM-Zuordnung.
- Ein UUID-Anfrageschlüssel verhindert eine zweite Belastung bei identischer Wiederholung. Derselbe Schlüssel mit verändertem Betrag oder Kontext wird abgewiesen. Abgelehnte Versuche erhalten weiterhin keinen Audit-Eintrag.
- Die im UI sichtbare Transaction-ID ist eine technische Kennung. Rollbacks können Lücken verursachen; sie ist daher keine lückenlose oder rechtliche Belegnummer.

Nach v0.4 waren Belegformat, Audit fehlgeschlagener Versuche, Tageslimits, Scheinannahme, PostgreSQL-Paralleltests, CI und Deployment offen.

## v0.5: Belege und verbindliches v1.0-Ziel

- Jede Transaction erhält beim Erstellen eine eindeutige Referenz aus dem Präfix `ATM-` und einer ULID. Vorhandene lokale Buchungen werden bei der Migration nachträglich ergänzt. Die technische Datenbank-ID wird nicht mehr als Belegnummer angezeigt.
- Ein erfolgreicher Vorgang führt auf eine eigene Belegseite. Ein idempotent wiederholter Request führt zum selben Beleg. Die Seite ist nur mit einer aktiven ATM-Sitzung und ausschließlich für das Sitzungskonto erreichbar.
- Konto und Karte werden auf dem Beleg maskiert. Auszahlungen zeigen außerdem Automat und gespeicherten Schein-Snapshot. Die Druckansicht blendet Navigation und Bedienelemente aus; sie ist ein Demo-Artefakt und kein steuerlicher oder rechtlicher Beleg.
- Das v1.0-Ziel umfasst eine lokale Vorführung und eine öffentlich erreichbare Showcase-Instanz. Damit werden PostgreSQL, geschützte Betreiber-Authentifizierung, HTTPS, sichere Produktionskonfiguration, CI und ein kontrollierter Demo-Reset verpflichtend.
- Der Hosting-Anbieter ist noch nicht gewählt. Diese Unsicherheit beeinflusst Datenbankdienst, Deployment, Backups, Monitoring und Kosten; die Entscheidung ist vor v0.8 fällig.

Die verbindliche Reihenfolge steht in [roadmap.md](roadmap.md). Als nächster Meilenstein folgt v0.6 mit Verwendungszweck sowie Filterung und Sortierung der Transaktionsübersicht; das Audit folgt in v0.7.

## Nachtrag zum v1.0-Umfang

- Ein- und Auszahlungen erhalten einen optionalen Verwendungszweck. Er gehört unveränderlich zur Transaction und erscheint auf Beleg und Übersicht.
- Die Transaktionsübersicht wird serverseitig nach Typ filterbar sowie nach Datum und Betrag in beide Richtungen sortierbar. Query-Parameter bleiben bei Pagination erhalten; eine umfangreiche freie Suche gehört nicht zum Pflichtumfang.
- Das Projekt war bis zu diesem Zeitpunkt noch kein lokales Git-Repository. GitHub ist für Repository und CI vorgesehen; GitHub Pages kann das Laravel-Backend nicht ausführen.
- Für den kostenlosen öffentlichen Showcase werden Render Free für den Webdienst und Neon Free für PostgreSQL verwendet. Koyeb schied aus, nachdem kostenlose Pläne für neue Nutzer nicht mehr verfügbar waren. Details und aktuelle Tarifgrenzen stehen in [hosting.md](hosting.md).

## v0.6: Verwendungszweck und Transaktionsübersicht

- Der optionale Verwendungszweck ist auf 140 Unicode-Zeichen begrenzt. Außenliegende und mehrfache Leerzeichen werden normalisiert; Steuerzeichen werden abgewiesen. Leere Eingaben werden als `null` gespeichert.
- Der Verwendungszweck ist Teil der idempotenten Buchungsabsicht. Derselbe Anfrageschlüssel mit geändertem Text wird abgewiesen, auch wenn Betrag und Karte gleich bleiben.
- Zweck, Typ und Betrag werden ausschließlich serverseitig aus der kontogebundenen Transaction gelesen. Der Text erscheint HTML-escaped in Historie und Beleg.
- Die Historie filtert serverseitig nach `deposit`, `withdrawal` oder `opening`. Sie sortiert nach `created_at` oder `amount_minor`, jeweils auf- oder absteigend. Die technische ID dient als deterministischer Tie-Breaker.
- Nicht unterstützte Query-Werte fallen auf alle Typen, Datum und absteigende Reihenfolge zurück. Query-Parameter werden in Pagination-Links übernommen.

Im lokalen Browserlauf wurden eine Einzahlung über 4,50 EUR mit „Browserprüfung v0.6“ und eine Auszahlung über 10,00 EUR mit „Testabhebung v0.6“ gebucht. DEMO-002 steht danach bei 0,00 EUR. Diese Testdaten werden nicht mit Git veröffentlicht.

## v0.7: Audit und Betreiberbereich

- Betreiber verwenden Laravels vorhandenes `User`-Modell mit dem expliziten Kennzeichen `is_operator`. Diese Identität bleibt fachlich getrennt von Customer, Card und der PIN-Sitzung. Eine Rollenbibliothek wäre für genau eine Rolle unnötig.
- Der lokale Seeder legt einen Betreiber mit konfigurierbarer E-Mail und konfigurierbarem Passwort nur in `local` und `testing` an. Eine Produktionsumgebung darf diesen bekannten Zugang nicht seeden und braucht einen gesonderten, geheimen Bereitstellungsweg.
- Betreiberanmeldungen werden pro normalisierter E-Mail und IP im Cache auf fünf Versuche pro Minute begrenzt. Weder E-Mail noch IP gelangen in den Audit-Kontext. Das Limit ist auf mehreren Webinstanzen nur mit gemeinsamem Cache konsistent.
- Der Betreiber kann ausschließlich den konfigurierten ATM zwischen `active` und `maintenance` wechseln sowie vorhandene Stückelungen um −100 bis +100 Scheine verändern. Fremde Automaten werden abgewiesen; ein negativer Zielbestand führt zum atomaren Rollback.
- AuditEvent erfasst Ereignistyp, Ergebnis, optionalen Grundcode, technische Fremdschlüssel und einen allowlist-basierten JSON-Kontext. PIN, Passwort, IP-Adresse und Buchungsverwendungszweck werden nicht kopiert. Abgedeckt sind Karten- und Betreiberanmeldung, Abmeldung beziehungsweise Sitzungsinvalidierung, Geldbewegungen sowie Betreiberänderungen.
- Audit-Ereignisse lassen sich über Eloquent weder ändern noch löschen. Das ist nachvollziehbare Anwendungskontrolle, aber kein kryptografisch verkettetes oder extern revisionssicher archiviertes Audit.
- Die Betreiberseite zeigt die 50 jüngsten Ereignisse. Pagination, Suche, Export, Aufbewahrungsdauer, Passwort-Reset und Mehrfaktor-Anmeldung sind vor der öffentlichen Veröffentlichung erneut zu bewerten; sie gehören nicht automatisch zum kleinen v1.0-Showcase.

Der nächste Meilenstein v0.8 prüft Migrationen und konkurrierende Auszahlungen mit PostgreSQL. Hostingbedingungen bleiben zeitabhängig; die aktuelle Entscheidung für Render Free plus Neon Free und ihre Grenzen stehen in [hosting.md](hosting.md).

## v0.8: PostgreSQL und Nebenläufigkeit

- Der sichtbare Name des Demo-Automaten lautet ab v0.8 **LERN-Bank Mein Geldautomat**. Eine kleine Datenmigration ändert nur den bekannten Datensatz `BER-DEMO-01`, wenn er noch den alten Standardnamen trägt; individuell geänderte Labels bleiben erhalten. Der Seeder gleicht den lokalen Demo-Datensatz dagegen bewusst an den aktuellen Projektnamen an.
- Die PIN kann weiterhin über eine physische Tastatur eingegeben werden. Zusätzlich gibt es ein semantisch beschriftetes 3×4-Nummernfeld mit Ziffern, Löschen und Rückschritt. Damit bleibt die normale Formvalidierung die einzige serverseitige PIN-Prüfung.
- PostgreSQL ist als unterstützte Ziel-Datenbank festgelegt; `ext-pdo_pgsql` ist nun eine explizite Plattformanforderung. SQLite bleibt für den schnellen lokalen Standardlauf erhalten.
- Der PostgreSQL-Test startet pro Szenario zwei eigenständige PHP-Prozesse hinter einer gemeinsamen Startbarriere. Geprüft werden zwei Auszahlungen gegen ein unzureichendes gemeinsames Kontoguthaben, zwei Konten gegen den letzten passenden Schein sowie zwei identische Anfragen mit demselben Idempotenzschlüssel.
- Die vorhandene Sperrfolge Account → Card → ATM → CashInventory serialisiert diese drei Fälle unter PostgreSQL 18.6 korrekt. Ein Vorgang wird bei fehlender Deckung beziehungsweise fehlendem Schein abgewiesen; identische Wiederholungen liefern dieselbe Transaction zurück.
- PostgreSQL-Tests sind opt-in, weil sie `migrate:fresh` auf der angegebenen Datenbank ausführen. `ATM_POSTGRES_TEST_URL` muss deshalb auf eine ausschließlich dafür vorgesehene Datenbank zeigen; als zusätzliche Fehlbedienungssperre muss deren Name auf `_test` enden. Der normale Testlauf bleibt unabhängig und nutzt SQLite im Speicher.

Die Tests sind reale lokale Mehrprozessprüfungen, aber kein Beweis für Verhalten bei Netzwerkabbrüchen, Prozessabstürzen, hoher Dauerlast oder anbieterspezifischen Proxy-/Pooling-Einstellungen. Diese Restunsicherheit gehört in den Deployment-Prototyp.

## v0.9: Browserprüfung und Produktionshärtung

- Playwright automatisiert den öffentlichen Kernablauf in lokal installiertem Chrome. Eine eigene, von Git ignorierte SQLite-Datei wird frisch migriert und geseedet und nach dem Lauf entfernt; lokale Showcase-Daten bleiben unangetastet.
- Der Ablauf prüft eine abgewiesene PIN, Nummernfeld-Anmeldung, Einzahlung, Auszahlungsbestand, beide Belege, neuen Automatennamen, Endsaldo und Abmeldung. Eine zweite Prüfung deckt physische Tastatureingabe, Rückschritt, Löschfunktion und 375-Pixel-Breite ab.
- Der erste automatisierte Lauf deckte ein inkonsistentes Fehlerziel auf: Nach Inertia-Navigation konnte Laravels allgemeines Zurück-Ziel bei falscher PIN auf die Landingpage weisen. Anmeldefehler setzen deshalb nun explizit die jeweilige Loginroute als Ziel.
- Basissicherheitsheader gelten für alle Webantworten. CSP wird nur ohne Debug-Modus aktiviert, damit Vites lokaler Entwicklungsserver nicht blockiert wird. HSTS wird zusätzlich ausschließlich bei HTTPS gesetzt.
- Die CSP erlaubt ausschließlich eigene Skripte, Bilder und Verbindungen; `style-src 'unsafe-inline'` bleibt vorläufig nötig, weil Inertia seinen Fortschrittsindikator dynamisch gestaltet. Das wird als begrenzter Kompromiss dokumentiert, nicht als vollständige XSS-Härtung.
- Bei deaktiviertem Debug-Modus erhalten typische Webfehler eine knappe Inertia-Fehlerseite. JSON-Antworten und lokale Debug-Antworten behalten Laravels reguläres Verhalten.

Für Produktion sind `APP_DEBUG=false`, eine HTTPS-URL und `SESSION_SECURE_COOKIE=true` erforderlich. Ob der Zielhost HTTPS und Proxyinformationen korrekt an Laravel weitergibt, lässt sich lokal nicht bestätigen und muss im v1.0-Deployment geprüft werden.

## v1.0-rc.1: Veröffentlichung vorbereiten

- Noch keine v1.0-Freigabe: Eine öffentlich erreichbare, am Zielhost geprüfte Instanz fehlt. Der Release Candidate setzt CI, Lizenz, kontrollierten Demo-Reset und den Deployment-Weg um.
- Der öffentliche Modus ist über `PUBLIC_DEMO_ENABLED` standardmäßig deaktiviert. Produktion erhält nur zwei fiktive Demo-Identitäten und einen ATM über einen eigenen idempotenten CLI-Provisionierungsweg. Bekannte lokale Betreiberpasswörter bleiben in Produktion ausgeschlossen; ein CLI-Befehl fragt ein eigenes Passwort verdeckt ab.
- Die Konten bleiben zwischen Besuchern geteilt. Öffentliche PINs sowie ein Hinweis auf geteilte Konten und ausschließlich erfundene Freitexte erscheinen direkt auf der Loginseite. Separate Besucher-Konten wären eine zusätzliche Produktentscheidung.
- Der Reset setzt bekannte Demo-Konten auf 0 €, verwirft deren Buchungen/Audits, stellt Karten und Scheinbestand wieder her und erhöht eine Kartensitzungsversion. Middleware und Buchungs-Actions prüfen diese Version, damit vor dem Reset vorbereitete Vorgänge nicht nachträglich buchen können. Fremde Konten und Betreiberbenutzer bleiben erhalten.
- Ein 24-Stunden-Intervall wird über eine gesperrte PostgreSQL-Zeile koordiniert. Die erste Besucheranfrage nach Ablauf löst den Reset aus; Ruhephasen brauchen keinen aktiven Scheduler. Ein manueller CLI-Reset erfordert zusätzlich `--force`; es gibt keine Reset-Webroute.
- Im ausdrücklich aktivierten Demo-Modus ist Audit-Aufbewahrung auf sieben Tage begrenzt; zugehörige Konto-Audits werden bereits beim Reset gelöscht. Diese kontrollierte Wartung verwendet Query Builder und umgeht bewusst den Eloquent-Löschschutz für kurzlebige erfundene Daten. Lokal passiert kein automatisches Löschen.
- Geldbewegungsanfragen werden im öffentlichen Modus zusätzlich auf 30 je IP/Minute begrenzt. Verteilte Anfragen, dauerhaftes Leeren innerhalb eines Intervalls und eine absolute Zeilen-/Quotenobergrenze sind nicht gelöst. Der Showcase ist kein ständig verfügbarer Mehrnutzer-Bankdienst.
- GitHub Actions verwendet PHP 8.4/Node 24, SQLite, PostgreSQL 18, Chromium und einen Produktionscontainer-Smoke-Test. Docker trennt Frontend-Build und PHP-/Apache-Runtime, verwendet Lockfiles und kopiert keine lokalen Secrets/Daten.
- Im Container sind maximal zwei Apache-Worker vorgesehen. Die 512-MB-Free-Instanz muss dennoch am Host gemessen werden. Basisimages sind auf Hauptversionen statt Digests festgelegt; OS/PHP-Patches können sich bei einem Neubau ändern.
- Der vorhandene MIT-Eintrag in Composer wird um eine tatsächliche MIT-Lizenzdatei ergänzt. Hostingkonten, Proxyvertrauen, TLS, Kaltstart, Speicher und Tarifbedingungen bleiben vor Veröffentlichung zu bestätigen.

## v1.0-rc.2: Showcase-Darstellung und Rückmeldung

- Die App bietet `light`, `dark` und `retro` als drei Darstellungen in einem gemeinsamen Umschalter. Die Wahl wird nur im Browser gespeichert; es gibt weder Benutzerprofil noch serverseitiges Tracking dieser Einstellung. Ohne gespeicherte Wahl gilt die Betriebssystempräferenz für dunkle Darstellung.
- Der Dark Mode überschreibt die bewusst kleine, vorhandene Tailwind-Farbpalette. Der Retro-Skin bleibt reines CSS mit Systemschrift, eckigen Flächen, versetzten Schatten und Scanlines. Es werden keine Bild-, Font- oder JavaScript-Abhängigkeiten ergänzt.
- Flash-Erfolgsmeldungen werden zentral als Toast ausgegeben. Validierungs- und Geschäftsfehler bleiben nahe am jeweiligen Formular, weil ein Erfolgssymbol für Fehlermeldungen irreführend wäre.
- Eine Inertia-Navigation zeigt erst nach 180 Millisekunden ein Lade-Overlay. Dadurch erhalten langsame Kaltstart- oder Netzwerkantworten eine klare Rückmeldung, während warme Antworten nicht durch kurzes Flackern unruhig wirken.
- GitHub verweist direkt auf das Showcase-Repository. `PORTFOLIO_URL` ist eine Laufzeitkonfiguration; ohne gesetzten Wert heißt der Link ausdrücklich „Portfolio-Code“ und verweist auf das Portfolio-Repository. Die lokal gefundene Domain `tiny-bits.org` wurde nicht fest eingetragen, weil ihre HTTPS-Zertifikatsprüfung derzeit fehlschlägt.
- Der Stand wird als `v1.0.0-rc.2` geführt. Die UI-Arbeit ändert die offene Freigabebedingung zur realen Render-Speichermessung nicht.

## v1.0: Öffentliche Freigabe

- Die Showcase-Adresse ist `https://atm.tiny-bits.org`; die Render-Subdomain bleibt als technische Rückfalladresse aktiv. Die Hauptdomain und das Portfolio verbleiben unverändert bei GitHub Pages.
- Render Free und Neon Free genügen für den vereinbarten Showcase. Der höchste während der zweiminütigen Startmessung beobachtete Containerverbrauch betrug 58,1 MiB von 512 MiB. Vier Apache-Worker bleiben deshalb bestehen; die kurze Messung ist keine Aussage über Dauerlast.
- Zwölf parallele Kaltstart-Anfragen benötigten 23,39 bis 24,05 Sekunden, warme Anfragen 0,13 bis 1,18 Sekunden. Der kostenlose Schlafmodus und wechselnde Aufwachzeiten werden als sichtbare Produkteinschränkung akzeptiert.
- DNS, verwaltetes TLS, HSTS, CSP, sichere Cookies, Proxyverhalten und der öffentliche ATM-Ablauf sind abgenommen. GitHub Actions prüft zusätzlich SQLite, PostgreSQL-Mehrprozessfälle, Chromium und das Produktionsimage.
- Der veröffentlichte Stand ist `v1.0.0`. Damit ist der vereinbarte Umfang abgeschlossen; daraus folgt keine Eignung für echte Bankdaten, reale Zahlungen, hohe Last oder zugesicherte Verfügbarkeit.

## v1.1: Klassik-ATM und Touchscreen

- Zwei zusätzliche Darstellungen verändern neben Farben und Formen auch die Bedienstruktur. Der Klassik-Modus orientiert sich an einem eingefassten Automatenbildschirm mit paarweise angeordneten Funktionstasten; der Touch-Modus verwendet große farbige Kacheln.
- Beide Modi nutzen dieselben Laravel-Routen, Formulare und serverseitigen Geschäftsregeln wie die Standardansicht. Eine separate Buchungslogik pro Skin wird bewusst vermieden.
- Nach der PIN-Anmeldung erscheint zunächst ein Hauptmenü für Kontostand, Einzahlung, Auszahlung, Umsätze und Kartenrückgabe. Beträge und PIN lassen sich per Bildschirmtastatur eingeben. Der optionale freie Verwendungszweck bleibt als Texteingabe verfügbar.
- Phosphor Icons liefert die funktionalen Symbole. Die drei bereitgestellten Bilder dienen ausschließlich als visuelle Referenz und werden nicht in die Anwendung oder das Repository kopiert.
- Der Stand wird als `v1.1.0` geführt.

## v1.2: Admin Control Center

- Der vorhandene geschützte Betreiberzugang wird unter `/admin` zu einem eigenständigen Admin-Bereich ausgebaut. Die bestehende `is_operator`-Berechtigung bleibt erhalten; ein zusätzliches Rollenpaket wäre für weiterhin genau eine Verwaltungsrolle unnötig.
- Das Dashboard liest dieselben Eloquent-Modelle wie der ATM und zeigt Kennzahlen, eine Sieben-Tage-Aktivität sowie höchstens 50 aktuelle Transaktionen und Audit-Ereignisse. Tabellenfilter laufen in dieser ersten Version im Browser; serverseitige Pagination ist bei der kleinen Demo-Datenmenge noch nicht notwendig.
- Schreibzugriffe bleiben bewusst auf den vorhandenen Umfang begrenzt: Automatenstatus und Stückzahl vorhandener Bargeldkassetten. Statusänderungen erscheinen als Modal, Bestandskorrekturen als seitliche Bearbeitungsleiste. Konten, Karten, Transaktionen und Audit-Ereignisse bleiben lesbar, aber nicht editierbar.
- Der Admin-Bereich verwendet eine eigene Material-inspirierte Oberfläche auf Basis der vorhandenen Tailwind-CSS-Pipeline und Phosphor Icons. Er übernimmt die ATM-Skins nicht, damit die Verwaltungsoberfläche visuell konsistent und vorhersehbar bleibt.
- Alte `/operator`-Endpunkte bleiben aus Kompatibilitätsgründen erreichbar; neue Oberflächen und Formulare verwenden `/admin`.
- Der Stand wird als `v1.2.0` geführt.

## v1.3: Konto- und Kartenverwaltung

- Ein Admin kann ein Konto entweder für eine bestehende Person oder zusammen mit einer neuen Person anlegen. Jedes neue Konto erhält unmittelbar eine erste Karte und startet mit 0 Cent; ein frei gesetzter Eröffnungssaldo würde die unveränderliche Buchungshistorie umgehen und wird deshalb nicht angeboten.
- Zusätzliche Karten werden einem vorhandenen Konto zugeordnet. Kartenreferenzen und Kontoreferenzen sind eindeutig, werden normalisiert und erlauben nur Großbuchstaben, Ziffern und Bindestriche. PINs bestehen weiterhin aus vier Ziffern und werden ausschließlich über Laravels Hash-Cast gespeichert.
- Konten und Karten können getrennt zwischen `active` und `blocked` wechseln. Jede Statusänderung erhöht die Sitzungsversion betroffener Karten; dadurch können vorher gestartete Sitzungen auch nach einer späteren Reaktivierung nicht weiterverwendet werden.
- PIN-Fehlversuche und eine temporäre Zeitsperre lassen sich gemeinsam zurücksetzen. Die PIN selbst wird nicht angezeigt, exportiert oder ins Audit geschrieben. Eine PIN-Neuvergabe bleibt wegen der höheren Missbrauchsfolgen außerhalb dieser Version.
- Löschen wird nicht angeboten. Buchungen und Audit-Ereignisse referenzieren Konten und Karten absichtlich mit restriktiven Fremdschlüsseln; Sperren erhält die Historie und ist reversibel.
- Alle Erzeugungs-, Status- und Entsperraktionen werden mit Admin, Zielobjekt und minimalem Vorher-/Nachher-Kontext auditiert. Namen, PINs und Passwörter bleiben aus dem Audit-Kontext ausgeschlossen.
- Der Stand wird als `v1.3.0` geführt.
