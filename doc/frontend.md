# Das Frontend: Modul, Ansichten und Bedienung

[← Zurück zur Übersicht](../README.md)

## Das Frontend-Modul einrichten

Das Frontend-Modul **„Forum"** (Typ `synapsis_forum`) zeigt **einen** Startpunkt an. Eine
einzige Modulinstanz stellt über URL-Parameter alle Ansichten dar.

**So wird es angelegt:** Unter **Layout → Module → Neu** den Typ **„Forum"** wählen und
einstellen:

| Einstellung             | Bedeutung                                                                 |
|-------------------------|---------------------------------------------------------------------------|
| **Startpunkt**          | Welcher Startpunkt in diesem Modul angezeigt wird (Pflichtfeld)           |
| **Einträge pro Seite**  | Anzahl Themen/Beiträge je Seite bei der Blätter-Navigation (Standard 20)  |
| **Editor mit Emoticons**| TinyMCE als Editor für Themen und Antworten (Fett, Kursiv, Listen, Links, Bilder); darunter eine **Smiley-Leiste** zum Einfügen von Emojis |
| **Dateianhänge erlauben** | Erlaubt das Hochladen von Anhängen; darunter der **Upload-Ordner**       |

Dazu die üblichen Contao-Felder (Name, Überschrift, Zugriffsschutz, CSS-ID).

**So wird es sichtbar:** Das Modul anschließend in eine Seite bringen – am einfachsten über
ein Inhaltselement **„Modul"** in einem Artikel, oder indem man es im **Seitenlayout** einer
Spalte zuweist.

Möchte man mehrere getrennte Foren anzeigen, legt man **je Startpunkt ein eigenes Modul** an
und bindet es auf einer eigenen Seite ein.

---

## Ansichten

Das Modul kennt mehrere Ansichten, die es automatisch über URL-Parameter umschaltet:

| Aufruf                | Ansicht                                                              |
|-----------------------|----------------------------------------------------------------------|
| (ohne Parameter)      | **Übersicht**: Kategorien mit Foren, neueste Themen, Statistiken      |
| `?category=<id>`      | **Kategorie**: nur die Foren dieser Kategorie                        |
| `?forum=<id>`         | **Forum**: Themenliste (seitenweise, angepinnte oben)                |
| `?topic=<id>`         | **Thema**: Beiträge mit Antwortformular, Umfrage usw.                |
| `?forum=<id>&new=1`   | **Neues Thema** anlegen                                              |
| `?member=<id>`        | **Mitgliederprofil**                                                 |
| `?q=<begriff>`        | **Suche** innerhalb des Startpunkts                                  |
| `?panel=…`            | **Mein Bereich** (persönliche Ansichten, siehe unten)                |
| `?feed=1` / `?forum=<id>&feed=1` | **RSS-Feed** des Startpunkts bzw. eines Forums            |

Auf jeder Seite gibt es außerdem eine **Pfadnavigation** (Brotkrumen) und eine **Suchbox**. In
der Pfadnavigation sind sowohl Foren als auch **Kategorien verlinkt**.

---

## Themen und Beiträge (für Mitglieder)

* **Neues Thema / Antworten** – Angemeldete Mitglieder mit Schreibrecht können Themen anlegen
  und antworten. Als Editor kommt **TinyMCE** zum Einsatz (Fett, Kursiv, Listen, Links,
  Bilder); zusätzlich gibt es eine **Smiley-Leiste** zum schnellen Einfügen von Emojis. Sind
  Dateianhänge erlaubt, lassen sich Dateien hochladen; Bilder werden im Beitrag eingebunden.
* **Gelöschte Konten** – Wird ein Mitgliedskonto später gelöscht, erscheint als Autor
  „Gast (früherer Benutzername)", damit die Beiträge zuordenbar bleiben.
* **Gefällt mir** – Beiträge können mit „Gefällt mir" markiert werden (nicht der eigene). Unter
  dem Beitrag stehen Anzahl und Namen.
* **Zitieren** – Über „Zitieren" wird der Beitrag als Zitat ins Antwortfeld übernommen; die
  zitierte Person erhält eine [Benachrichtigung](benachrichtigungen.md).
* **Bearbeiten / Löschen** – Den **eigenen** Beitrag darf man nachträglich bearbeiten oder
  löschen, solange das Thema offen ist. Nach dem Bearbeiten erscheint ein Hinweis „Zuletzt
  bearbeitet von … am …". Moderatoren/Administratoren dürfen fremde Beiträge bearbeiten/löschen,
  sofern es die globalen Einstellungen erlauben.
* **Melden** – Verstößt ein Beitrag gegen die Regeln, kann er der **Moderation gemeldet**
  werden (mit kurzer Begründung). Die zuständigen Moderatoren/Administratoren sehen die Meldung
  unter „Mein Bereich → Meldungen".
* **@Erwähnungen** – Wird im Text `@Benutzername` geschrieben, erhält das genannte Mitglied
  eine [Benachrichtigung](benachrichtigungen.md).
* **Abonnieren** – Ein **Thema** lässt sich abonnieren (E-Mail bei neuer Antwort); ein ganzes
  **Forum** ebenso (E-Mail bei jedem neuen Thema).
* **Gelesen/Ungelesen** – Für angemeldete Mitglieder werden ungelesene Themen und Foren
  markiert; die ungelesenen Beiträge sind über „Mein Bereich" abrufbar.
* **Suche** – Die Suchbox durchsucht Themen-Titel, Beitragstexte und Umfragen **innerhalb des
  Startpunkts**.
* **Mitgliederprofil** – Ein Klick auf einen Autornamen öffnet dessen Profil (Avatar, Rangstufe,
  Mitglied-seit-Datum, Beitragszahl im Startpunkt, Signatur und die letzten Beiträge).
* **Permalink** – Jeder Beitrag hat einen **„Link"** (Direktlink samt Sprungmarke); ein Klick
  kopiert die Adresse in die Zwischenablage.
* **Als gelesen markieren** – In einem Forum lässt sich **„Forum als gelesen markieren"**, auf der
  Übersicht **„Alles als gelesen markieren"** (jeweils für angemeldete Mitglieder).
* **Bild-Lightbox** – Bild-Anhänge öffnen sich per Klick als **Vollbild-Overlay** (Schließen per
  Klick oder Esc).
* **RSS-Feed** – Übersicht und Forenansicht bieten einen **RSS-Feed** der neuesten Themen
  (Schaltfläche „RSS", zusätzlich zur automatischen Feed-Erkennung im Browser).
* **Entwürfe** – Der Text im Antwort- bzw. Neues-Thema-Feld wird **lokal im Browser**
  zwischengespeichert; verlässt man die Seite, lässt sich der Entwurf beim nächsten Aufruf
  **wiederherstellen** (oder verwerfen). Beim Absenden wird er automatisch verworfen.
* **„Wer ist online"** – Auf der Übersicht erscheinen (sofern in den
  [Einstellungen](einstellungen.md) aktiv) die in den letzten 5 Minuten aktiven Mitglieder
  samt Gästezahl.

## Avatare

Jedes Mitglied erhält automatisch ein farbiges Standard-Avatar (Lucide). Ist zusätzlich das
Bundle [terminal42/contao-avatar](https://github.com/terminal42/contao-avatar) installiert,
wird stattdessen der dort gepflegte Mitglieder-Avatar verwendet.

---

## Umfragen

Beim Anlegen eines Themas kann – sofern man dazu [berechtigt](knoten-einstellungen.md#umfragen-erstellen)
ist – optional eine **Umfrage** mitgegeben werden:

* **Frage** und **Antwortmöglichkeiten** (eine pro Zeile, mindestens zwei).
* **Einfachauswahl** (eine Antwort) oder **Mehrfachauswahl** (mehrere Antworten).
* **Umfrageende** (Datum **und Uhrzeit**, Pflicht) – danach ist keine Stimmabgabe mehr möglich.
  Ohne Uhrzeitangabe gilt das Ende des gewählten Tages.
* Option **„Ergebnisse erst nach Umfrageende anzeigen"** – sonst erscheinen sie direkt nach der
  eigenen Stimmabgabe. Nach dem Ende sind die Ergebnisse immer sichtbar.

In der Themenansicht stimmen angemeldete Mitglieder ab; danach werden die Ergebnisse als Balken
mit Prozent und Teilnehmerzahl angezeigt. Doppelt abstimmen ist ausgeschlossen.

---

## Mein Bereich

Angemeldete Mitglieder finden auf jeder Forenseite unten die Box **„Mein Bereich"** mit
persönlichen Ansichten:

* **Benachrichtigungen** – Das persönliche Postfach (Antworten, Zitate, Erwähnungen). Neben dem
  Menüpunkt zeigt ein **Zähler** die ungelesenen Benachrichtigungen an; beim Öffnen gelten sie
  als gelesen. Siehe [Benachrichtigungen](benachrichtigungen.md).
* **Meine Beiträge** – Themen, in denen man selbst geschrieben hat.
* **Ungelesene Beiträge** – Themen mit noch nicht gelesenen Beiträgen.
* **Gefällt mir** – Themen mit von einem selbst markierten Beiträgen.
* **Abonnements** – Abonnierte Themen verwalten (einzeln abbestellen).
* **Signatur** – Die eigene Signatur bearbeiten. Erlaubt sind einfache **BB-Codes**
  (`[b]`, `[i]`, `[u]`, `[s]`, `[url]`, `[color]`) über bequeme Einfüge-Buttons; die Signatur
  erscheint unter den eigenen Beiträgen.
* **Meldungen** – Nur für **Moderatoren/Administratoren** sichtbar: offene Beitragsmeldungen mit
  Begründung; jede lässt sich mit „Erledigt" abschließen.
* **Sperren** – Nur für Berechtigte sichtbar: die gesperrten Mitglieder verwalten. Siehe
  [Moderation → Mitglieder sperren](moderation.md#mitglieder-sperren-bann).

Alle Ansichten von „Mein Bereich" sind **auf den Startpunkt begrenzt** – fremde Startpunkte
tauchen darin nicht auf.
