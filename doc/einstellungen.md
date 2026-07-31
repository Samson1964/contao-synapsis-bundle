# Globale Einstellungen

[← Zurück zur Übersicht](../README.md)

Im Backend-Modul **Synapsis-Forum → Einstellungen** (gilt über alle Startpunkte hinweg):

## Darstellung

* **Farbschema** – Wählt das Farbschema für die Frontend-Darstellung über eine **anklickbare
  Farbvorschau** (wie beim Icon-Wizard): **Standard** (Blau), **Petrol**, **Gold**, **Rot**,
  **Orange** oder **Grün**. Die Schemata ändern nur die Farben; ein eigenes Schema lässt sich
  als CSS-Klasse `synapsis-scheme--<name>` in einer Custom-CSS ergänzen.

## Community & Mitglieder

* **„Wer ist online" anzeigen** – Auf der Übersicht erscheinen die aktuell aktiven Mitglieder
  (der letzten 5 Minuten) samt Gästezahl.
* **Rangstufen anzeigen** – Bei Beiträgen und im Profil erscheint eine Rangstufe nach
  Beitragszahl. Die Stufen sind frei konfigurierbar (je Zeile „Mindestbeiträge|Titel"); leer =
  Standardstufen (Neuling, Mitglied, Stammgast, Erfahren, Veteran).

## Administration

* **Wortfilter** – Ersetzt konfigurierte Wörter in Beitragstexten und Titeln
  (je Zeile „Wort" → Sternchen oder „Wort=Ersatz"). Verglichen wird als ganzes Wort, ohne
  Beachtung der Groß-/Kleinschreibung; HTML/Links bleiben unangetastet. Leer = aus.

## E-Mail-Benachrichtigungen

* **E-Mail-Benachrichtigungen aktiv** – Zentraler An/aus-Schalter für **alle** E-Mails.
* **Betreff- und Text-Vorlage** für die Antwort-Benachrichtigung an Themen-Abonnenten.
  Platzhalter: `##topic##` (Thementitel), `##name##` (Empfänger), `##url##` (Adresse des Themas).

## Team-Benachrichtigung

Bei neuen Beiträgen zusätzlich das **Team** informieren:

* **Administratoren benachrichtigen** und/oder **Moderatoren benachrichtigen** (jeweils an/aus).
* **Auslöser** – bei **neuen Themen**, **Antworten** oder **beidem**.
* **Betreff- und Text-Vorlage** mit den Platzhaltern `##forum##`, `##topic##`, `##author##`
  (Verfasser) und `##url##`.

## Absender (optional)

Name und E-Mail-Adresse für ausgehende Forum-Mails; leer = Contao-Standardabsender.

## Rechte der Moderatoren

Feinsteuerung, was **Moderatoren** tun dürfen (**Administratoren** dürfen immer alles):

| Einstellung                                   | Standard |
|-----------------------------------------------|----------|
| Moderatoren dürfen Themen **anpinnen**        | an       |
| Moderatoren dürfen Themen **schließen**       | an       |
| Moderatoren dürfen Themen **verschieben**     | an       |
| Moderatoren dürfen fremde Beiträge **bearbeiten/löschen** | an |
| Moderatoren dürfen Mitglieder **sperren**     | **aus**  |

Die Rechte gelten auch für die [Massen-Moderation](moderation.md#massen-moderation); das
Sperren ist bewusst standardmäßig aus, siehe
[Mitglieder sperren](moderation.md#mitglieder-sperren-bann).
