# Import aus Fremdsystemen

[← Zurück zur Übersicht](../README.md)

Über **Synapsis-Forum → Import** lassen sich Inhalte aus einem Fremdsystem übernehmen.
Zur Auswahl stehen ein **phpBB-Forum** (CSV-Export) und – falls vorhanden – das
**Support-Ticket-System** (Fast-Media) aus der aktuellen Datenbank. Beide Wege hängen den
Import in eine **Kategorie** ein: die fremden Foren entstehen als Foren darunter.

> Hinweis für beide Formate: Importierte Foren werden zunächst **öffentlich lesbar** angelegt
> („Gäste dürfen lesen"), damit die Inhalte sichtbar sind – der Zugriffsschutz lässt sich
> anschließend je Forum anpassen. Liegt die Ziel-Kategorie in einem **geschützten**
> Startpunkt, bleibt dessen Schutz bestehen: die importierten Foren sind dann nur für die
> erlaubten Mitgliedergruppen sichtbar (siehe
> [Gäste-Zugriff](knoten-einstellungen.md#gäste-zugriff)).

## Import aus phpBB

Grundlage ist ein **CSV-Export der phpBB-Tabellen** (in phpMyAdmin je Tabelle als CSV
exportieren).

**Ablauf:**

1. Im Backend unter **Forum** einen Startpunkt und darin die **Ziel-Kategorie** anlegen.
2. Unter **Import** die Ziel-Kategorie wählen (Anzeige „Startpunkt › Kategorie") und die
   CSV-Dateien hochladen.
3. Anschließend **auswählen, welche Foren** übernommen werden sollen, und den Import starten.

**Dateien** (die Spalten entsprechen dem phpBB-Schema):

| Datei | Pflicht | Inhalt |
|-------|---------|--------|
| `phpbb_forums.csv` | ja | Die Foren. Nur echte Foren werden übernommen – phpBB-**Kategorien** (Container) werden übersprungen. |
| `phpbb_topics.csv` | ja | Die Themen (Titel, Datum, Aufrufe, angeheftet, geschlossen). |
| `phpbb_posts.csv`  | ja | Die Beiträge. Der phpBB-Text (BBCode bzw. XML) wird nach HTML gewandelt. |
| `phpbb_users.csv`  | optional | Liefert die Anzeigenamen registrierter Verfasser. |
| `phpbb_poll_options.csv` | optional | Für die Übernahme von Umfragen (Frage, Antworten, Ergebnis). |

**Was übernommen wird:** Foren, Themen, Beiträge (mit Formatierung, Links, Zitaten, Listen),
Aufruf-Zähler und Umfragen (samt Ergebnis). **Verfasser** werden als **Gast** mit ihrem
phpBB-Namen abgelegt (Anzeige „Gast (Name)"), da die phpBB-Konten im Zielsystem fremd sind.
**Nicht übernommen** werden private Nachrichten und Datei-Anhänge.

## Import aus dem Support-Ticket-System

Ist das **Support-Ticket-System** (Fast-Media) in derselben Contao-Datenbank installiert
(Tabellen `tl_support_*`), erscheint unter **Import** zusätzlich das Format
**„Support-Ticket-System (aktuelle Datenbank)"**. Hier ist **kein Datei-Upload** nötig – die
Daten werden direkt aus der laufenden Datenbank gelesen.

**Ablauf:** Ziel-Kategorie wählen, Format „Support-Ticket-System" auswählen, dann die zu
übernehmenden Foren markieren und den Import starten.

**Was übernommen wird:**

| Support-Tabelle | wird zu | Inhalt |
|-----------------|---------|--------|
| `tl_support_archive` (Typ `forum`/`support`) | Forum | Titel, Teaser → Beschreibung |
| `tl_support_ticket` | Thema | Titel, Datum, Aufrufe (`hits`), gesperrt (`closed`) |
| `tl_support_comment` | Beitrag | Der Kommentartext (bereits HTML) bleibt erhalten |

Der **Eröffnungsbeitrag** eines Themas ist der **älteste Kommentar** des Tickets (das Ticket
selbst hat keinen eigenen Text). **Verfasser** sind echte Contao-Mitglieder und werden **1:1**
übernommen (die `member_id` wird direkt als Autor gesetzt, der Anzeigename kommt live aus
`tl_member`) – anders als beim phpBB-Import, bei dem fremde Konten als Gast abgelegt werden.
Leere Tickets und unveröffentlichte Kommentare werden übersprungen; `tl_support_category`,
`tl_support_notify` und Datei-Anhänge bleiben unberücksichtigt.
