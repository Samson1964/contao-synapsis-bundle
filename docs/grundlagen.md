# Grundlagen: Startpunkte, Backend und Struktur

[← Zurück zur Übersicht](../README.md)

## Das Grundkonzept: Startpunkte

Das wichtigste Konzept von Synapsis sind die **Startpunkte**.

> **Ein Startpunkt ist ein eigenständiges, in sich abgeschlossenes Forum.** Alles, was darin
> passiert – Kategorien, Foren, Themen, Beiträge, Statistiken, Zähler, Umfragen, gelesen/
> ungelesen – bleibt **innerhalb dieses Startpunkts** und vermischt sich nicht mit anderen.

Dadurch können auf **einer** Contao-Installation **mehrere, voneinander getrennte Foren**
nebeneinander bestehen – zum Beispiel ein öffentliches Forum, ein internes Vereinsforum und
ein Support-Forum. Jedes hat seine eigene Struktur, seinen eigenen Zugriffsschutz und seine
eigene Frontend-Seite. Sie „wissen" nichts voneinander.

Global (also über alle Startpunkte hinweg) sind nur drei Dinge: die **Benutzer-Avatare**, die
[globalen Einstellungen](einstellungen.md) (u. a. E-Mail-Vorlagen) und die
[Mitglieder-Sperren](moderation.md#mitglieder-sperren-bann).

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

## Die Backend-Bereiche

Nach der Installation gibt es im Backend die Navigationsgruppe **„Synapsis-Forum"** mit vier
Modulen:

| Modul             | Zweck                                                                        |
|-------------------|------------------------------------------------------------------------------|
| **Forum**         | Die Forenstruktur (Startpunkte, Kategorien, Foren) und ihre Themen/Beiträge  |
| **Import**        | Ein Fremdsystem-Forum übernehmen: phpBB-CSV-Export oder Support-Ticket-System (Fast-Media) – siehe [Import](import.md) |
| **Statistik**     | Lese-Übersicht über alle Startpunkte – siehe [unten](#das-statistik-modul)   |
| **Einstellungen** | Globale Einstellungen – siehe [Globale Einstellungen](einstellungen.md)      |

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
ein Forum enthält Themen. Kategorien selbst können keine Themen enthalten. Unterhalb eines
Forums lässt sich nichts einfügen – die entsprechende Schaltfläche ist im Baum deaktiviert.

**Nur einen Knoten anzeigen:** Ein Klick auf den **Namen** eines Startpunkts, einer Kategorie
oder eines Forums grenzt den Baum auf diesen Knoten ein (wie in der Contao-Seitenstruktur).
Über dem Baum erscheint dann ein **Navigationspfad**: „Alle" hebt die Eingrenzung auf, die
Zwischenknoten führen eine Ebene höher. Die Auswahl bleibt gemerkt, bis sie zurückgesetzt wird.

Welche Einstellungen die einzelnen Knoten haben (Zugriffsschutz, Gäste, Rollen, Icons usw.),
beschreibt [Einstellungen je Knoten](knoten-einstellungen.md).

---

## Das Statistik-Modul

Das Backend-Modul **Synapsis-Forum → Statistik** zeigt eine reine **Lese-Übersicht** (es wird
nichts verändert):

* **Gesamtzahlen** – Startpunkte, Kategorien, Foren, Themen, Beiträge, aktive Mitglieder
  (Mitglieder mit mindestens einem Beitrag) und offene Meldungen.
* **Je Startpunkt** – Eine Aufstellung mit der Zahl der Foren, Themen und Beiträge je
  Startpunkt.
* **Aktivste Mitglieder** – Die zehn Mitglieder mit den meisten Beiträgen (über alle
  Startpunkte).
* **Letzte Beiträge** – Die zehn neuesten Beiträge mit Thema, Verfasser und Datum.

Leere Abschnitte (etwa „Aktivste Mitglieder" ohne Mitglieds-Beiträge) werden ausgeblendet.
