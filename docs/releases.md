# Releases und Versionierung

## Zielbild

Das Repository verwendet Semantic Versioning und Release Please. Ein Push auf `main` wertet seit dem letzten Release Conventional Commits aus und öffnet oder aktualisiert genau einen Release-Pull-Request. Dieser Pull-Request enthält:

- die nächste Version in `package.json` und `package-lock.json`,
- dieselbe Version in `docs/api/openapi.json`,
- den automatisch erzeugten Abschnitt in `CHANGELOG.md`,
- eine Zusammenfassung für das spätere GitHub Release.

Wird der Release-Pull-Request zusammengeführt, erzeugt derselbe Workflow den Git-Tag `vX.Y.Z` und ein veröffentlichtes GitHub Release. Es gibt absichtlich keinen automatischen Deployment-Schritt: Release und Deployment sind in diesem Showcase getrennte Entscheidungen.

## Versionsquelle

Die aktuelle Anwendungsversion steht in `package.json`; die Laufzeitanzeige liest sie über `config/app.php`. `package-lock.json` und die OpenAPI-Metadaten werden automatisch synchron gehalten. Die Manifestdatei `.release-please-manifest.json` speichert den zuletzt freigegebenen Stand für Release Please.

Obwohl dies ein Laravel-Projekt ist, verwendet die Release-Please-Konfiguration den Release-Typ `node`. Das bedeutet nicht, dass die Anwendung als npm-Paket veröffentlicht wird. Der Typ wurde gewählt, weil er die bereits vorhandenen npm-Versionsfelder zuverlässig gemeinsam aktualisiert. `composer.json` bleibt ohne `version`: Composer ermittelt Versionen bei VCS-Projekten aus Git-Tags, und das Paket ist ausdrücklich vom Typ `project`.

Der Startwert ist `1.5.0`, weil README, `package.json`, `package-lock.json` und die dokumentierten Meilensteine diesen Stand nennen. Die fehlenden Tags von `v0.5.0` bis `v0.9.0`, die beiden v1.0-Release-Candidates sowie `v1.2.0` bis `v1.5.0` wurden anhand der jeweiligen Versionsänderungen in `package.json`, README und `docs/decisions.md` rekonstruiert. Sie zeigen auf die Commits, die den jeweiligen Stand erstmals vollständig ausweisen; ihre spätere Erstellung ändert weder Commit-Zeitpunkte noch Inhalte. Die nach `v1.5.0` eingecheckte Lokalisierung bleibt im historischen Changelog als unveröffentlicht gekennzeichnet.

Für v0.1 bis v0.4 gibt es keine getrennten Git-Commits: Der erste Commit enthält bereits den Stand v0.5.0. Diese Versionen werden deshalb im Changelog erwähnt, aber nicht durch künstliche Tags vorgetäuscht.

`bootstrap-sha` setzt den Ausgangspunkt auf den letzten Commit vor Einführung der Automation. Dadurch verarbeitet der erste Release-Pull-Request nicht versehentlich die ungetaggte Historie zwischen v1.1.0 und dem bereits dokumentierten Stand 1.5.0. Nach dem ersten durch Release Please erzeugten Release wird dieser Wert ignoriert und kann entfernt werden.

## Commit-Format

Release Please bestimmt Versionssprünge aus Commits auf `main`:

| Präfix | Wirkung | Beispiel |
| --- | --- | --- |
| `fix:` | Patch, zum Beispiel 1.5.0 → 1.5.1 | `fix: prevent stale ATM session reuse` |
| `feat:` | Minor, zum Beispiel 1.5.0 → 1.6.0 | `feat: add transaction export` |
| `feat!:` oder `BREAKING CHANGE:` im Footer | Major, zum Beispiel 1.5.0 → 2.0.0 | `feat!: replace the public session API` |
| `docs:`, `test:`, `refactor:`, `perf:`, `build:`, `ci:` | kein eigener Release-Zwang; erscheint beim nächsten Release im passenden Abschnitt | `docs: explain demo reset limits` |
| `chore:` | kein Versionssprung und im Changelog verborgen | `chore: refresh editor settings` |

Ein optionaler Scope macht die Historie lesbarer, etwa `fix(auth): reject expired cards`. Die Beschreibung steht im Imperativ, bleibt kurz und erhält keinen abschließenden Punkt.

Der Workflow `conventional-pr.yml` prüft Pull-Request-Titel auf dieses Format. Das allein reicht technisch nicht, wenn GitHub beim Merge einen anders formulierten Commit erzeugt. Deshalb muss die Repository-Einstellung für Squash-Merges den Pull-Request-Titel als Commit-Titel übernehmen, oder der finale Commit muss vor dem Merge manuell conventional formuliert werden. Diese GitHub-Einstellung lässt sich nicht aus dem Repository heraus erzwingen.

## Ablauf

1. Änderungen werden über einen Pull Request nach `main` gebracht; die normale CI muss erfolgreich sein.
2. Der Pull-Request-Titel beziehungsweise der resultierende Commit auf `main` folgt dem oben beschriebenen Format.
3. Nach dem Merge aktualisiert der Release-Workflow den offenen Release-Pull-Request. Mehrere Änderungen werden dort gesammelt.
4. Vor dem Release werden Changelog, Versionssprung und erfolgreiche CI des enthaltenen Anwendungsstands geprüft.
5. Das Zusammenführen des Release-Pull-Requests erstellt Tag und GitHub Release. Tags und Versionsdateien werden nicht von Hand gepflegt.

Ein Release lässt sich bewusst zurückstellen, indem der Release-Pull-Request offen bleibt. Für einen ausdrücklich gewünschten Versionssprung kann ein Commit-Footer `Release-As: 1.7.0` verwendet werden; er sollte nicht dauerhaft in der Konfiguration hinterlegt werden.

## Erforderliche GitHub-Einstellungen und Grenzen

Der Workflow verwendet ausschließlich das automatisch bereitgestellte `GITHUB_TOKEN`; es wird kein persönliches Token oder externer Dienst vorausgesetzt. In den Repository-Einstellungen muss GitHub Actions das Erstellen von Pull Requests erlaubt sein. Release Please benötigt Schreibrechte für Repository-Inhalte, Issues und Pull Requests; sie sind in `release.yml` ausdrücklich auf diesen Workflow begrenzt.

### Manuelle Einrichtung auf GitHub

1. Repository **Settings → Actions → General** öffnen. Unter **Workflow permissions** die Option **Allow GitHub Actions to create and approve pull requests** aktivieren und speichern. Die pauschale GitHub-Option umfasst auch Freigaben; der vorhandene Release-Workflow enthält jedoch keinen Schritt, der Pull Requests genehmigt.
2. Unter **Settings → General → Pull Requests** mindestens **Allow squash merging** aktivieren. Im zugehörigen Auswahlfeld **Default to pull request title** wählen. Für eine eindeutig lineare, durch den geprüften PR-Titel bestimmte Historie sollten Merge-Commits und Rebase-Merges deaktiviert bleiben.
3. Unter **Settings → Rules → Rulesets** einen aktiven Branch-Ruleset für den Default-Branch `main` anlegen. Alternativ kann die ältere Oberfläche **Settings → Branches → Add branch protection rule** verwendet werden.
4. Im Ruleset mindestens **Require a pull request before merging**, **Require status checks to pass before merging**, **Block force pushes** und **Restrict deletions** aktivieren. **Require conversation resolution before merging** und **Require linear history** sind für dieses Repository ebenfalls sinnvoll.
5. Nachdem die Workflows mindestens einmal in einem Pull Request gelaufen sind, die Checks `CI / verify`, `CI / container` und `Conventional PR title / title` als erforderlich auswählen. Namen nur aus GitHubs Auswahlliste übernehmen; nicht anhand dieser Dokumentation frei eintippen.
6. Einen Test-Pull-Request mit einem Titel wie `docs: verify release workflow` anlegen. Der Titelcheck und beide CI-Jobs müssen erscheinen. Für einen tatsächlichen Release-Test ist ein `fix:`- oder `feat:`-Titel nötig, weil reine Dokumentationsänderungen keinen Versionssprung auslösen.

Der Release-Please-Pull-Request benötigt eine Sonderbetrachtung. GitHubs aktuelle Dokumentation beschreibt für die Ereignisse `opened`, `synchronize` und `reopened` inzwischen eine Ausnahme: Ein mit `GITHUB_TOKEN` erzeugter oder aktualisierter Pull Request kann Workflow-Läufe im Zustand **approval required** anlegen. In diesem Fall zeigt die Merge-Box **Approve workflows to run**; ein Benutzer mit Schreibzugriff muss die Läufe freigeben. Die Release-Please-Dokumentation warnt weiterhin allgemeiner davor, dass mit `GITHUB_TOKEN` erzeugte Ressourcen keine nachfolgenden Workflows auslösen. Deshalb ist das im konkreten Repository sichtbare Verhalten maßgeblich. Fehlen die Läufe vollständig, kann der fertig aktualisierte Release-Pull-Request einmal geschlossen und durch einen Menschen wieder geöffnet werden; danach den Freigabebanner erneut prüfen.

Wenn diese manuelle Freigabe vermieden werden soll, ist ein fein begrenztes GitHub-App-Token oder Fine-grained Personal Access Token eine mögliche Alternative. Es benötigt für Release Please Zugriff auf dieses Repository sowie **Contents: Read and write**, **Issues: Read and write** und **Pull requests: Read and write**. Das Token wird als Repository-Secret, beispielsweise `RELEASE_PLEASE_TOKEN`, gespeichert und in `release.yml` ausdrücklich als `token` an die Action übergeben. Ein persönliches Token bringt Rotation und Abhängigkeit vom Benutzerkonto mit sich; eine GitHub App ist langfristig sauberer, aber für dieses Einzelprojekt aufwendiger.

Der eigentliche Anwendungsstand muss bereits über seine vorangegangenen Pull Requests geprüft worden sein; zusätzlich sind die von Release Please erzeugten Änderungen an Changelog und Versionsdateien zu kontrollieren. Falls im konkreten Repository trotz Freigabe kein frischer CI-Check auf dem Release-Pull-Request entsteht, wäre für einen vollständig automatischen Ablauf ein GitHub-App-Token oder fein begrenztes PAT nötig. Ein solches Secret wird hier bewusst nicht erfunden.

Die Action ist passend zum bestehenden CI-Stil auf die Hauptversion `v4` festgelegt, nicht auf einen Commit-SHA. Das ist wartungsarm, aber weniger strikt gegen Änderungen am Action-Tag abgesichert. Eine spätere Supply-Chain-Härtung kann alle Actions auf geprüfte Commit-SHAs pinnen; sie sollte dann konsequent auch den vorhandenen CI-Workflow umfassen.
