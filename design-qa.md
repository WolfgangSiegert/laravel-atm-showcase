# Design QA — Klassik-ATM und Touchscreen

## Vergleichsbasis

- Klassik-Referenz: `/Users/Wolfgang/Downloads/atm-machine-menu-driven-interface.jpg` (640 × 480 px).
- Touch-Referenzen: `/Users/Wolfgang/Downloads/monitoring-dashboard-lifestyle-atm-payment-application-user-interface-vector-illustration-online-modern-screen-kiosk-flat-111767477.webp` (800 × 800 px) und `/Users/Wolfgang/Downloads/original-3513f511fbe504b5b88c3004de524a07.webp` (400 × 300 px).
- Implementierung Klassik: `docs/design/classic-atm-menu.png` (1280 × 720 px).
- Implementierung Touch: `docs/design/touchscreen-menu.png` (1280 × 720 px).
- CSS-Viewport der finalen Desktop-Aufnahmen: 1280 × 720, Device Scale Factor 1.
- Zustand: angemeldeter Demo-Benutzer, Hauptmenü direkt nach der PIN-Anmeldung.

Die Referenzen sind Inspirationsbilder mit unterschiedlichen Seitenverhältnissen und fremden Funktionsumfängen. Verglichen wurden deshalb die beauftragten Designmerkmale: klassische paarweise Menüführung im eingefassten Automatenbildschirm beziehungsweise ein modernes, farbcodiertes Kachelmenü mit großen Touch-Zielen. Eine pixelgenaue Reproduktion der Marken, Texte oder nicht vorhandenen Fremdfunktionen war nicht beabsichtigt.

## Sichtbarer Vergleich

### Vollansicht

- Klassik übernimmt den dunklen Gerätekorpus, den hellen Bildschirm, die violett-blauen Funktionstasten, die symmetrische Links-/Rechts-Anordnung und die knappe Automatenbeschriftung der Referenz. Die Hardwaretasten sind als direkt klickbare Webtasten innerhalb des Bildschirms umgesetzt.
- Touch übernimmt das dunkelblaue Umfeld, die helle Bedienfläche, große türkis-/korall-/blaue Kacheln, klare Linienicons und die visuelle Priorisierung einer breiten Abschlussaktion.
- Beide Varianten behalten LERN-Bank-Branding, vorhandene Navigation und deutsche Texte. Das ist eine bewusste Produktintegration statt einer Kopie der Beispielmarken.

### Detailprüfung

- **Typografie:** Klassik nutzt eine robuste Arial/Helvetica-Hierarchie mit kurzen Schaltflächentexten; Touch nutzt Helvetica Neue mit großen, gut lesbaren Titeln. Keine kritischen Umbrüche in der Desktop-Aufnahme.
- **Abstände und Rhythmus:** Klassik besitzt enge, gleichmäßige Tastenpaare; Touch arbeitet mit großzügigen Kacheln und 0,8-rem-Raster. Die 375-Pixel-Browserprüfung bestätigt für beide Modi eine Dokumentbreite exakt entsprechend dem Viewport.
- **Farben und Tokens:** Klassik verwendet Anthrazit, warmes Bildschirmgelb und dunkles Violett; Touch verwendet Marineblau, Cyan, Türkis, Koralle und Pink. Kontrast und Fokusmarkierung bleiben erhalten.
- **Icons und Bildqualität:** Funktionale Symbole stammen aus Phosphor Icons. Die Referenzbilder werden nicht als App-Assets verwendet. Die Screenshots zeigen scharfe vektorbasierte Symbole ohne Platzhalter.
- **Text und Inhalt:** Alle Kacheln bilden reale vorhandene Funktionen ab: Kontostand, Einzahlung, Auszahlung, Umsätze und Sitzung beenden. Nicht implementierte Fremdfunktionen aus den Referenzen wurden nicht vorgetäuscht.

## Interaktionen und technische Prüfung

- Klassik- und Touch-Auswahl werden in `localStorage` gespeichert.
- Demo-Karte und PIN können in beiden Modi per Mausklick gewählt beziehungsweise eingegeben werden.
- Das Hauptmenü öffnet Kontostand, Einzahlung, Auszahlung und Umsätze und kehrt jeweils ins Hauptmenü zurück.
- Beträge lassen sich über das Bildschirm-Nummernfeld eingeben; der optionale Verwendungszweck bleibt eine freie Texteingabe.
- Kartenrückgabe beendet die Sitzung.
- Vier Chrome-End-to-End-Abläufe einschließlich der neuen Menüs sind erfolgreich.
- Browserkonsole während der visuellen Prüfung: keine Warnungen oder Fehler.

## Vergleichshistorie

1. Die erste Browseraufnahme verwendete den sehr breiten Standard-Viewport. Dadurch wirkten beide Oberflächen im kombinierten Vergleich unnötig klein; dies war ein Capture-Problem und kein Layoutfehler.
2. Die Implementierung wurde bei 1280 Pixel Breite erneut aufgenommen. Der Folgevergleich zeigt die Menühierarchie, Tastenproportionen, Farbflächen, Typografie und Icons ausreichend groß. Es blieben keine P0-, P1- oder P2-Abweichungen.

## Restliche P3-Punkte

- Die klassische Webvariante simuliert die seitlichen Hardwaretasten als beschriftete Bildschirmtasten. Echte unbeschriftete Tasten außerhalb des Screens wären näher am Foto, würden im Browser aber die direkte Zuordnung und Bedienbarkeit verschlechtern.
- Die Touch-Ansicht behält die globale LERN-Bank-Navigation über der Bedienfläche. Die Referenzen zeigen isolierte Geräte-Screens; für den Showcase ist der Wechsel zwischen allen fünf Darstellungen wichtiger.

final result: passed
