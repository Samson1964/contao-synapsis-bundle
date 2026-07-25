# Synapsis – Forum für Contao

Synapsis ist ein vollwertiges Forum als Erweiterung für **Contao 4.13 und Contao 5**. Die
Benutzerverwaltung läuft komplett über die Contao-Mitglieder; Layout und Bedienung
orientieren sich an [forum.mybb.de](https://forum.mybb.de/).

Dieses Dokument ist ein **Handbuch für Anwender** – also für Redakteurinnen und Redakteure,
die im Backend ein Forum einrichten, sowie für die Mitglieder, die es im Frontend nutzen.

## Inhalt

1. [Das Grundkonzept: Startpunkte](#das-grundkonzept-startpunkte)
2. [Installation](#installation)
3. [Schnellstart: In wenigen Schritten zum sichtbaren Forum](#schnellstart-in-wenigen-schritten-zum-sichtbaren-forum)
4. [Die Backend-Bereiche](#die-backend-bereiche)
5. [Die Forenstruktur aufbauen](#die-forenstruktur-aufbauen)
6. [Einstellungen je Knoten (Startpunkt, Kategorie, Forum)](#einstellungen-je-knoten)
7. [Das Frontend-Modul einrichten](#das-frontend-modul-einrichten)
8. [Ansichten und Bedienung im Frontend](#ansichten-und-bedienung-im-frontend)
9. [Mein Bereich (persönliche Ansichten)](#mein-bereich)
10. [Moderation: Rollen und Anpinnen](#moderation-rollen-und-anpinnen)
11. [Umfragen](#umfragen)
12. [Globale Einstellungen](#globale-einstellungen)
13. [Import aus anderen Foren (CSV)](#import-aus-anderen-foren-csv)
14. [Warum wird mein Forum nicht angezeigt? (Checkliste)](#warum-wird-mein-forum-nicht-angezeigt)
15. [Für Entwickler](#für-entwickler)

---

## Das Grundkonzept: Startpunkte

Das wichtigste Konzept von Synapsis sind die **Startpunkte**.

> **Ein Startpunkt ist ein eigenständiges, in sich abgeschlossenes Forum.** Alles, was darin
> passiert – Kategorien, Foren, Themen, Beiträge, Statistiken, Zähler, Umfragen, gelesen/
> ungelesen – bleibt **innerhalb dieses Startpunkts** und vermischt sich nicht mit anderen.

Dadurch können auf **einer** Contao-Installation **mehrere, voneinander getrennte Foren**
nebeneinander bestehen – zum Beispiel ein öffentliches Forum, ein internes Vereinsforum und
ein Support-Forum. Jedes hat seine eigene Struktur, seinen eigenen Zugriffsschutz und seine
eigene Frontend-Seite. Sie „wissen" nichts voneinander.

Global (also über alle Startpunkte hinweg) sind nur zwei Dinge: die **Benutzer-Avatare** und
die **E-Mail-Vorlagen** für Benachrichtigungen.

Die gesamte Struktur liegt in einer Baumtabelle und wird im Backend ähnlich der
Seitenstruktur verwaltet. Jeder Knoten hat einen **Typ**:

| Typ            | Bedeutung                                                    | Erlaubt darunter |
|----------------|--------------------------------------------------------------|------------------|
| **Startpunkt** | Eine eigenständige Forenstruktur (nur auf oberster Ebene)    | Kategorien       |
| **Kategorie**  | Gruppiert Foren, enthält selbst **keine** Themen             | Foren            |
| **Forum**      | Enthält die Themen, die im Frontend erstellt werden          | (nur Themen)     |

Die Struktur ist bewusst klar gegliedert: **Startpunkt → Kategorie → Forum → Thema → Beitrag**.
Themen und Beiträge entstehen im Frontend durch die Mitglieder.

---

## Installation

Im **Contao Manager** das Paket `schachbulle/contao-synapsis-bundle` installieren, oder per
Composer:

```bash
composer require schachbulle/contao-synapsis-bundle
```

Anschließend **die Datenbank aktualisieren** (Contao Manager → Systemwartung → „Datenbank
aktualisieren", oder `vendor/bin/contao-console contao:migrate`). Nach jedem Update sollte
die Datenbankaktualisierung erneut ausgeführt werden.

---

## Schnellstart: In wenigen Schritten zum sichtbaren Forum

Damit ein Forum im **Frontend erscheint**, sind fünf Schritte nötig:

1. **Struktur anlegen** – Im Backend unter **Synapsis-Forum → Forum** einen **Startpunkt**
   erstellen, darunter mindestens eine **Kategorie** und darin mindestens ein **Forum**.
2. **Alles veröffentlichen** – Startpunkt, Kategorie und Forum müssen auf „veröffentlicht"
   stehen (grünes Häkchen in der Baumstruktur). Unveröffentlichte Knoten sind unsichtbar.
3. **Frontend-Modul anlegen** – Unter **Layout → Module** ein Modul vom Typ **„Forum"**
   erstellen und dort den gewünschten **Startpunkt** auswählen.
4. **Modul in eine Seite einbinden** – Das Modul in einem Artikel über das Inhaltselement
   **„Modul"** platzieren (oder im Seitenlayout einer Spalte zuweisen).
5. **Zugriff prüfen** – Sollen auch **Gäste** (nicht angemeldete Besucher) mitlesen, muss beim
   Forum (oder weiter oben) „Gäste dürfen lesen" aktiviert sein. Sonst sehen nur angemeldete
   Mitglieder der erlaubten Gruppen den Inhalt.

Fertig – die Seite mit dem Modul zeigt jetzt die Forenübersicht des gewählten Startpunkts.

---

## Die Backend-Bereiche

Nach der Installation gibt es im Backend die Navigationsgruppe **„Synapsis-Forum"** mit drei
Modulen:

| Modul             | Zweck                                                                        |
|-------------------|------------------------------------------------------------------------------|
| **Forum**         | Die Forenstruktur (Startpunkte, Kategorien, Foren) und ihre Themen/Beiträge  |
| **CSV Import**    | Foren, Themen und Beiträge aus einer anderen Software (z. B. phpBB) übernehmen |
| **Einstellungen** | Globale Einstellungen (E-Mail-Vorlagen, Rechte der Moderatoren)              |

---

## Die Forenstruktur aufbauen

Im Modul **Forum** wird die Struktur wie die Contao-Seitenstruktur bearbeitet:

1. Mit **„Neu"** einen Knoten anlegen. Auf oberster Ebene entsteht automatisch ein
   **Startpunkt**, darunter eine **Kategorie**, darunter ein **Forum** – der Typ wird passend
   zur Position vorgeschlagen und über das Feld **Typ** festgelegt.
2. Reihenfolge per Drag-and-drop ändern; mit dem Stift-Symbol bearbeiten; mit dem
   Augen-Symbol veröffentlichen bzw. verbergen.
3. Über das **Themen-Symbol** eines Forums gelangt man zu dessen Themen und Beiträgen (die
   normalerweise im Frontend entstehen, hier aber auch bearbeitet werden können).

**Regeln der Hierarchie:** Ein Startpunkt enthält Kategorien, eine Kategorie enthält Foren,
ein Forum enthält Themen. Kategorien selbst können keine Themen enthalten.

---

## Einstellungen je Knoten

Jeder Knoten (Startpunkt, Kategorie, Forum) hat – je nach Typ – folgende Einstellungen. **Fast
alle vererben sich nach unten:** Was am Startpunkt eingestellt ist, gilt für alle Kategorien
und Foren darunter, kann dort aber ergänzt werden.

### Bezeichnung und Beschreibung

* **Bezeichnung / Alias** – Der Name und die (automatisch erzeugte) URL-Kennung.
* **Beschreibung** – Ein kurzer Text, der im Frontend unter dem Forennamen erscheint.

### Forum-Icon

Ein **Lucide-Icon** je Bereich, wählbar über ein anklickbares Symbol-Raster (nach Kategorien
geordnet, u. a. echte Schachfiguren). Wird vererbt: Der Startpunkt gibt ein Standard-Icon vor,
Kategorie und Forum dürfen es überschreiben. Leer = erben.

### Geschlossen (nur Forum)

Ist ein Forum **geschlossen**, können darin keine neuen Themen oder Antworten mehr verfasst
werden; gelesen werden darf weiterhin.

### Zugriffsschutz (Mitglieder)

* **Zugriff schützen** – Aktiviert den Schutz für diesen Bereich und alles darunter.
* **Erlaubte Mitgliedergruppen** – Nur Mitglieder dieser Contao-Gruppen sehen den Bereich.

Ist ein Bereich nicht geschützt, ist er (Standard von Contao) öffentlich lesbar.

### Gäste-Zugriff

Für **nicht angemeldete Besucher** (Gäste) getrennt regelbar und ebenfalls vererbt:

* **Gäste dürfen lesen** – Öffentlicher Lesezugriff (auch für Mitglieder ohne passende Gruppe).
* **Gäste dürfen schreiben** – Erlaubt Gästen zusätzlich das Anlegen von Themen und Antworten.

> Hinweis: Ist die fiktive Gruppe „Gäste" bereits in den erlaubten Mitgliedergruppen
> ausgewählt, dürfen Gäste dort nur **lesen**; die Schreib-Checkbox bleibt dann ohne Wirkung.

### Administratoren und Moderatoren

Je Knoten lassen sich – über **Mitgliedergruppen** und/oder **einzelne Mitglieder** –
**Administratoren** und **Moderatoren** festlegen. Beide Rollen vererben sich nach unten. Was
Moderatoren dürfen, wird global in den [Einstellungen](#globale-einstellungen) geregelt
(derzeit: Themen anpinnen); Administratoren dürfen dies immer.

### Umfragen erstellen

Je Knoten lässt sich – über **Gruppen** und/oder **einzelne Mitglieder** – festlegen, wer
beim Anlegen eines Themas eine **Umfrage** erstellen darf. Auch dieses Recht vererbt sich nach
unten. Ist nirgends etwas vergeben, darf niemand Umfragen anlegen.

### Veröffentlichung

Nur **veröffentlichte** Knoten sind im Frontend sichtbar. Ist ein übergeordneter Knoten
unveröffentlicht, ist der gesamte Bereich darunter unsichtbar.

---

## Das Frontend-Modul einrichten

Das Frontend-Modul **„Forum"** (Typ `synapsis_forum`) zeigt **einen** Startpunkt an. Eine
einzige Modulinstanz stellt über URL-Parameter alle Ansichten dar.

**So wird es angelegt:** Unter **Layout → Module → Neu** den Typ **„Forum"** wählen und
einstellen:

| Einstellung             | Bedeutung                                                                 |
|-------------------------|---------------------------------------------------------------------------|
| **Startpunkt**          | Welcher Startpunkt in diesem Modul angezeigt wird (Pflichtfeld)           |
| **Einträge pro Seite**  | Anzahl Themen/Beiträge je Seite bei der Blätter-Navigation (Standard 20)  |
| **Editor mit Emoticons**| TinyMCE als Editor für Themen und Antworten (mit Smiley-/Emoji-Auswahl)   |
| **Dateianhänge erlauben** | Erlaubt das Hochladen von Anhängen; darunter der **Upload-Ordner**       |

Dazu die üblichen Contao-Felder (Name, Überschrift, Zugriffsschutz, CSS-ID).

**So wird es sichtbar:** Das Modul anschließend in eine Seite bringen – am einfachsten über
ein Inhaltselement **„Modul"** in einem Artikel, oder indem man es im **Seitenlayout** einer
Spalte zuweist.

Möchte man mehrere getrennte Foren anzeigen, legt man **je Startpunkt ein eigenes Modul** an
und bindet es auf einer eigenen Seite ein.

---

## Ansichten und Bedienung im Frontend

Das Modul kennt vier Ansichten, die es automatisch über URL-Parameter umschaltet:

| Aufruf                | Ansicht                                                              |
|-----------------------|----------------------------------------------------------------------|
| (ohne Parameter)      | **Übersicht**: Kategorien mit Foren, neueste Themen, Statistiken      |
| `?forum=<id>`         | **Forum**: Themenliste (seitenweise, angepinnte oben)                |
| `?topic=<id>`         | **Thema**: Beiträge mit Antwortformular, Umfrage usw.                |
| `?forum=<id>&new=1`   | **Neues Thema** anlegen                                              |

Auf jeder Seite gibt es außerdem eine **Pfadnavigation** (Brotkrumen) und eine **Suchbox**.

### Themen und Beiträge (für Mitglieder)

* **Neues Thema / Antworten** – Angemeldete Mitglieder mit Schreibrecht können Themen anlegen
  und antworten. Als Editor kommt **TinyMCE** zum Einsatz (Fett, Kursiv, Listen, Links, Bilder,
  **Emoji-Auswahl**); zusätzlich gibt es eine **Smiley-Leiste** zum schnellen Einfügen. Sind
  Dateianhänge erlaubt, lassen sich Dateien hochladen; Bilder werden im Beitrag eingebunden.
* **Gelöschte Konten** – Wird ein Mitgliedskonto später gelöscht, erscheint als Autor
  „Gast (früherer Benutzername)", damit die Beiträge zuordenbar bleiben.
* **Gefällt mir** – Beiträge können mit „Gefällt mir" markiert werden (nicht der eigene). Unter
  dem Beitrag stehen Anzahl und Namen.
* **Abonnieren** – Ein Thema lässt sich abonnieren; bei neuen Antworten gibt es eine E-Mail.
* **Gelesen/Ungelesen** – Für angemeldete Mitglieder werden ungelesene Themen und Foren
  markiert; die ungelesenen Beiträge sind über „Mein Bereich" abrufbar.
* **Suche** – Die Suchbox durchsucht Themen-Titel und Beitragstexte **innerhalb des
  Startpunkts**.

### Avatare

Jedes Mitglied erhält automatisch ein farbiges Standard-Avatar (Lucide). Ist zusätzlich das
Bundle [terminal42/contao-avatar](https://github.com/terminal42/contao-avatar) installiert,
wird stattdessen der dort gepflegte Mitglieder-Avatar verwendet.

---

## Mein Bereich

Angemeldete Mitglieder finden auf jeder Forenseite unten die Box **„Mein Bereich"** mit
persönlichen Ansichten:

* **Meine Beiträge** – Themen, in denen man selbst geschrieben hat.
* **Ungelesene Beiträge** – Themen mit noch nicht gelesenen Beiträgen.
* **Gefällt mir** – Themen mit von einem selbst markierten Beiträgen.
* **Abonnements** – Abonnierte Themen verwalten (einzeln abbestellen).
* **Signatur** – Die eigene Signatur bearbeiten. Erlaubt sind einfache **BB-Codes**
  (`[b]`, `[i]`, `[u]`, `[s]`, `[url]`, `[color]`) über bequeme Einfüge-Buttons; die Signatur
  erscheint unter den eigenen Beiträgen.

---

## Moderation: Rollen und Anpinnen

Wer in einem Bereich **Administrator** oder **Moderator** ist, wird je Knoten festgelegt (siehe
[Einstellungen je Knoten](#einstellungen-je-knoten)) und vererbt sich nach unten.

* **Anpinnen** – In der Themenansicht können Berechtigte ein Thema **oben anpinnen** bzw. wieder
  lösen. Angepinnte Themen erscheinen in der Themenliste zuoberst und tragen ein Badge.
* **Administratoren** dürfen immer anpinnen. **Moderatoren** dürfen es nur, wenn es in den
  globalen Einstellungen erlaubt ist.

---

## Umfragen

Beim Anlegen eines Themas kann – sofern man dazu berechtigt ist – optional eine **Umfrage**
mitgegeben werden:

* **Frage** und **Antwortmöglichkeiten** (eine pro Zeile, mindestens zwei).
* **Einfachauswahl** (eine Antwort) oder **Mehrfachauswahl** (mehrere Antworten).
* **Umfrageende** (Datum, Pflicht) – danach ist keine Stimmabgabe mehr möglich.
* Option **„Ergebnisse erst nach Umfrageende anzeigen"** – sonst erscheinen sie direkt nach der
  eigenen Stimmabgabe. Nach dem Ende sind die Ergebnisse immer sichtbar.

In der Themenansicht stimmen angemeldete Mitglieder ab; danach werden die Ergebnisse als Balken
mit Prozent und Teilnehmerzahl angezeigt. Doppelt abstimmen ist ausgeschlossen.

---

## Globale Einstellungen

Im Backend-Modul **Synapsis-Forum → Einstellungen** (gilt über alle Startpunkte hinweg):

* **E-Mail bei neuer Antwort** – An/aus, sowie **Betreff- und Text-Vorlage** für die
  Benachrichtigung an Abonnenten. Platzhalter: `##topic##` (Thementitel), `##name##`
  (Empfänger), `##url##` (Adresse des Themas).
* **Absender** (optional) – Name und E-Mail; leer = Contao-Standardabsender.
* **Rechte der Moderatoren** – u. a. **„Moderatoren dürfen Themen anpinnen"**.

---

## Import aus anderen Foren (CSV)

Über **Synapsis-Forum → CSV Import** lässt sich eine bestehende Struktur (z. B. aus phpBB)
übernehmen. Der Import erfolgt über **zwei CSV-Dateien** und wird unter einen Startpunkt oder
eine Kategorie eingehängt:

* **Struktur-Datei** – Kategorien und Foren. Spalten: `ref, parent, type, title, alias,
  description, forumIcon, closed, protected, groups, guestRead, guestWrite, published`.
* **Inhalt-Datei** (optional) – Themen und Beiträge. Spalten: `forum, topic, type, title,
  author, authorName, date, text, sticky, locked, published, views`. Dabei verweist `forum` auf
  die `ref` eines Forums der Struktur-Datei und `topic` gruppiert die Beiträge unter ihr Thema.

So lassen sich Fremdsystem-IDs (etwa phpBB `forum_id`/`topic_id`) direkt abbilden; Beiträge
ohne zuordenbares Konto werden als Gast mit dem ursprünglichen Namen übernommen.

---

## Warum wird mein Forum nicht angezeigt?

Wenn im Frontend nichts (oder eine leere Übersicht) erscheint, hilft diese Checkliste:

* Ist ein **Startpunkt** angelegt und **veröffentlicht**?
* Gibt es darunter mindestens **ein Forum** (Typ „forum"), das **veröffentlicht** ist? (Eine
  Kategorie ohne sichtbares Forum wird nicht angezeigt.)
* Ist das **Frontend-Modul „Forum"** angelegt und der **richtige Startpunkt** ausgewählt?
* Ist das Modul in eine **Seite eingebunden** (Inhaltselement „Modul" oder Layout-Spalte)?
* Darf die betrachtende Person **lesen**? Für Gäste muss „Gäste dürfen lesen" aktiv sein; sonst
  ist Anmeldung mit passender Mitgliedergruppe nötig.
* Ist die **Seite selbst** bzw. das Modul nicht durch einen Contao-Zugriffsschutz für andere
  Gruppen gesperrt?
* Nach einem Update: wurde die **Datenbank aktualisiert** (`contao:migrate`) und wurden die
  Assets neu veröffentlicht?

---

## Für Entwickler

```bash
vendor/bin/phpunit          # Unit-Tests
vendor/bin/ecs check src    # Code-Style (Contao Coding Standards)
```

Konventionen des Bundles:

* `declare(strict_types=1);` in jeder PHP-Datei; Kommentare und Labels auf Deutsch.
* DCA-Callbacks über den Service-Tag `contao.callback` (nicht über die `config.php`) – bewusst
  als YAML-Tag statt PHP-Attribut, damit PHP 7.4 unterstützt bleibt.
* Der DCA-Treiber wird versionsabhängig gesetzt (`DC_Table::class` bzw. `'Table'`), damit
  dieselbe DCA unter Contao 4.13 und Contao 5 funktioniert.

Die wiederverwendbare Fachlogik liegt in `src/Frontend/` in bewusst framework-unabhängigen,
unit-getesteten Klassen (u. a. `ForumAccess`, `PollAccess`, `RoleAccess`, `PollManager`,
`ReadTracker`, `LikeManager`, `BBCode`, `AuthorLabel`, `AvatarResolver`, `NotificationTemplate`).

## Lizenz

LGPL-3.0-or-later
