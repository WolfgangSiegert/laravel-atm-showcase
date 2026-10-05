# Datenschutztechnische Hinweise

Dieses Dokument beschreibt nur die technische Datenminimierung des Showcases und ist keine Rechtsberatung oder fertige Datenschutzerklärung.

## Aggregierte Nutzungsmetriken

Ist `USAGE_METRICS_ENABLED` aktiviert, speichert die Anwendung je Kalendertag nur Summen für öffentliche Start- und Seitenaufrufe sowie erfolgreiche ATM-Anmeldungen, Einzahlungen und Auszahlungen. Die Tabelle enthält Datum, einen fest definierten Metriknamen und einen Zähler. Sie enthält keine IP-Adresse, Browserkennung, Session-ID, Cookie-ID, Benutzer-ID, URL, Querywerte, Routenparameter oder User-Agent-Daten. Die Anwendung setzt dafür keinen Analyse-Cookie und versucht nicht, Endgeräte oder Personen wiederzuerkennen.

Die Kennzahlen dürfen daher nicht als „Besucher“, „Unique Visitors“ oder „Besuche“ bezeichnet werden. Sie geben ausschließlich Aufrufe und Aktionen wieder. Tageszeilen außerhalb der über `USAGE_METRICS_RETENTION_DAYS` konfigurierten Frist werden beim nächsten erfolgreichen Inkrement gelöscht; der Standard beträgt 400 Tage. Zweck, Rechtsgrundlage und Aufbewahrung müssen trotzdem in den tatsächlichen Datenschutzhinweisen transparent benannt werden.

## Technische Protokolle

Das davon getrennte Traffic- und Fehler-Logging dient kurzfristig Betrieb, Fehlersuche und Sicherheit. Seine Felder, Kanäle und Aufbewahrung sind in [observability.md](observability.md) dokumentiert. Hostinganbieter können IP-Adressen technisch verarbeiten, obwohl die Anwendung sie weder in `usage_metrics` noch in ihren strukturierten Traffic-Feldern speichert. Auftragsverarbeitung, Plattformprotokolle und Löschfristen müssen deshalb anhand der realen Hostingkonfiguration geprüft werden.

## Portfolio-Traffic

Die separat aktivierbare Portfolio-Erfassung hat eine reguläre Aufbewahrungsfrist von 90 Tagen. Da die Bereinigung opportunistisch beim nächsten akzeptierten Request läuft, können abgelaufene Ereignisse bei ausbleibendem Traffic physisch länger vorhanden bleiben. Pro Ereignis werden Zeitpunkt, der feste Site-Wert, ein allowlist-validierter Pfad und ein HMAC gespeichert. Der HMAC entsteht serverseitig aus der von Laravel ermittelten Client-IP und einer groben Browser-/Plattformklasse; Schlüssel ist der stabile `APP_KEY`. IP-Adresse und vollständiger User-Agent werden nicht gespeichert. Der Browser setzt dafür keinen Analyse-Cookie und sendet keine Zugangsdaten.

Die Kennung ist pseudonym und deshalb datenschutzrechtlich nicht mit anonymen Summen gleichzusetzen. Sie nähert unterschiedliche Geräte beziehungsweise Personen nur an: Mehrere Menschen hinter derselben IP mit gleicher Browserklasse können zusammenfallen, während IP-, Browser- oder Plattformwechsel eine Person aufteilen können. Die private Anzeige verwendet deshalb „Kennungen“ und nicht „Personen“ oder „Unique Visitors“. Weitere technische Details stehen in [portfolio-traffic.md](portfolio-traffic.md).

JoinSplit wird davon strikt getrennt verarbeitet. Die Anwendung speichert nur
einen Tageszähler für die feste Kategorie `/app`, ohne Einzelereignis,
IP(-Hash), User-Agent, Besucherkennung, Cookie, Referrer, Querywert oder
fachliche JoinSplit-Daten. Diese Summen messen App-Aufrufe und dürfen nicht als
eindeutige oder wiederkehrende Menschen interpretiert werden. Der User-Agent
wird ausschließlich flüchtig für den groben Botfilter betrachtet; technische
Verarbeitung durch Hosting- und Netzwerkkomponenten bleibt davon unberührt.

## Rechtliche Unsicherheit

Dass die Nutzungsmetriken keine Informationen im Endgerät speichern oder auslesen, reduziert das Einwilligungsrisiko für diesen konkreten Analysevorgang. Daraus folgt keine pauschale Ausnahme von DSGVO, TDDDG oder Informationspflichten. Die rechtliche Bewertung hängt unter anderem von tatsächlichem Zweck, Hosting, technischer Konfiguration und den veröffentlichten Datenschutzhinweisen ab.

Offizielle Ausgangspunkte für die Einzelfallprüfung sind [§ 25 TDDDG](https://www.gesetze-im-internet.de/ttdsg/__25.html), [Art. 6 und 13 DSGVO](https://eur-lex.europa.eu/legal-content/DE/ALL/?uri=CELEX%3A32016R0679) und die [DSK-Orientierungshilfe für Anbieter von Telemedien](https://www.datenschutzkonferenz-online.de/media/oh/20221205_oh_Telemedien_2021_Version_1_1_Vorlage_104_DSK_final.pdf). Die DSK-Unterlage stammt von Dezember 2022 und verwendet deshalb noch die damalige Bezeichnung TTDSG; sie ersetzt keine aktuelle rechtliche Prüfung.
