# Häufige Fragen und Problemlösung

[← Zurück zur Übersicht](../README.md)

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

## Nach einem Update richtig aktualisieren

Die sichere Reihenfolge nach jedem Update des Bundles:

1. **Anwendungscache neu aufbauen** – im Contao Manager („Systemwartung → Prod-Cache
   erneuern") oder auf der Konsole:

   ```bash
   vendor/bin/contao-console cache:clear
   ```

   Wichtig: Das Contao-Backend-„Daten bereinigen" reicht **nicht**, wenn sich Service- oder
   Modul-Registrierungen geändert haben – dann muss der **Symfony-Anwendungscache** neu gebaut
   werden (bei aktiviertem OPcache anschließend PHP neu starten).

2. **Datenbank aktualisieren**:

   ```bash
   vendor/bin/contao-console contao:migrate
   ```

   > Tipp: Bringt ein Update **neue Datenbankfelder** mit, erkennt der Schema-Vergleich sie
   > erst nach dem Cache-Neuaufbau. Im Zweifel `contao:migrate` nach einem `cache:clear`
   > einfach **erneut** ausführen.

3. **Assets neu veröffentlichen** (CSS/JS des Bundles):

   ```bash
   vendor/bin/contao-console assets:install
   ```

Welche Schritte ein konkretes Update braucht, steht jeweils im
[CHANGELOG](../CHANGELOG.md).

## Weitere Antworten

* **Die Themen-/Beitragszähler wirken falsch?** Alle Zähler und Statistiken sind bewusst
  **auf den Startpunkt begrenzt** – ein Mitglied mit Beiträgen in zwei Startpunkten hat in
  jedem seine eigene Beitragszahl.
* **Ein Mitglied kann plötzlich nicht mehr schreiben?** Prüfen, ob es
  [gesperrt](moderation.md#mitglieder-sperren-bann) wurde („Mein Bereich → Sperren"), ob das
  Thema geschlossen bzw. das Forum geschlossen ist, oder ob der Schreibzugriff über die
  [Knoten-Einstellungen](knoten-einstellungen.md) fehlt.
* **Gast statt Name beim Import?** Beim Support-Ticket-Import werden Verfasser über ihre
  `member_id` aufgelöst – existiert das Mitglied in dieser Installation nicht, erscheint
  „Gast". Siehe [Import](import.md).
