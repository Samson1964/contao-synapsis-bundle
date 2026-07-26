# Synapsis – Forum für Contao

Synapsis ist ein vollwertiges Forum als Erweiterung für **Contao 4.13 und Contao 5**. Die
Benutzerverwaltung läuft komplett über die Contao-Mitglieder; Layout und Bedienung
orientieren sich an [forum.mybb.de](https://forum.mybb.de/).

**Funktionsumfang im Überblick:**

* **Mehrere getrennte Foren** auf einer Installation (Startpunkte) mit Kategorien und Foren,
  verwaltet in einer Baumstruktur wie die Contao-Seitenstruktur.
* **Zugriffsschutz mit Vererbung** – Mitgliedergruppen, getrennte Gäste-Rechte (lesen/schreiben).
* **Themen und Beiträge** mit TinyMCE-Editor, Smiley-Leiste, Dateianhängen, Bild-Lightbox,
  Zitieren, „Gefällt mir", Signaturen (BB-Code), Permalinks und lokalen Entwürfen.
* **Umfragen** (Einfach-/Mehrfachauswahl, Enddatum, Ergebnis-Sichtbarkeit).
* **Community**: Mitgliederprofile, Rangstufen, „Wer ist online", gelesen/ungelesen,
  Forensuche, RSS-Feeds.
* **Mein Bereich**: Benachrichtigungscenter, eigene/ungelesene/markierte Beiträge,
  Abonnements, Signatur.
* **Benachrichtigungen** im Forum und per E-Mail (Themen-/Forum-Abos, @Erwähnungen,
  Team-Benachrichtigung, anpassbare Vorlagen).
* **Moderation**: vererbte Admin-/Moderatoren-Rollen, Anpinnen, Schließen, Verschieben,
  Meldungen, **Massen-Moderation** und **Mitglieder-Sperren** – Rechte fein steuerbar.
* **Administration**: Wortfilter, Statistik-Übersicht, fünf Farbschemata.
* **Import** aus **phpBB** (CSV-Export) und aus dem **Support-Ticket-System** (Fast-Media).

## Installation

Im **Contao Manager** das Paket `schachbulle/contao-synapsis-bundle` installieren, oder per
Composer:

```bash
composer require schachbulle/contao-synapsis-bundle
```

Anschließend **die Datenbank aktualisieren** (Contao Manager → Systemwartung → „Datenbank
aktualisieren", oder `vendor/bin/contao-console contao:migrate`). Nach jedem Update sollte
die Datenbankaktualisierung erneut ausgeführt werden – die empfohlene Reihenfolge steht in
den [häufigen Fragen](doc/faq.md#nach-einem-update-richtig-aktualisieren).

## Schnellstart: In wenigen Schritten zum sichtbaren Forum

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

## Dokumentation

Das ausführliche Handbuch liegt im Ordner [`doc/`](doc/):

| Thema | Inhalt |
|-------|--------|
| [Grundlagen](doc/grundlagen.md) | Das Startpunkt-Konzept, die Backend-Bereiche, die Forenstruktur, das Statistik-Modul |
| [Einstellungen je Knoten](doc/knoten-einstellungen.md) | Zugriffsschutz, Gäste, Rollen, Icons, Umfrage-Rechte, Veröffentlichung |
| [Frontend](doc/frontend.md) | Modul einrichten, alle Ansichten, Bedienung für Mitglieder, Umfragen, Avatare, „Mein Bereich" |
| [Moderation](doc/moderation.md) | Rollen, Anpinnen/Schließen/Verschieben, Meldungen, Massen-Moderation, Mitglieder sperren |
| [Benachrichtigungen](doc/benachrichtigungen.md) | Benachrichtigungscenter und E-Mails |
| [Globale Einstellungen](doc/einstellungen.md) | Farbschema, Community, Wortfilter, E-Mail-Vorlagen, Rechte der Moderatoren |
| [Import](doc/import.md) | Übernahme aus phpBB und aus dem Support-Ticket-System |
| [Häufige Fragen](doc/faq.md) | „Warum wird mein Forum nicht angezeigt?", Update-Reihenfolge, Problemlösung |
| [Für Entwickler](doc/entwicklung.md) | Kompatibilität, Struktur, Datenbank, Tests, Konventionen |

Die Änderungen je Version dokumentiert das [CHANGELOG](CHANGELOG.md).

## Lizenz

LGPL-3.0-or-later
