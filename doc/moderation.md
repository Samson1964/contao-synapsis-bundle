# Moderation

[← Zurück zur Übersicht](../README.md)

Wer in einem Bereich **Administrator** oder **Moderator** ist, wird je Knoten festgelegt (siehe
[Einstellungen je Knoten](knoten-einstellungen.md#administratoren-und-moderatoren)) und vererbt
sich nach unten. In der Themenansicht stehen Berechtigten oben die Moderationsschaltflächen zur
Verfügung:

* **Anpinnen** – Ein Thema **oben anpinnen** bzw. wieder lösen. Angepinnte Themen erscheinen in
  der Themenliste zuoberst und tragen ein Badge.
* **Schließen / Öffnen** – Ein geschlossenes Thema kann gelesen, aber nicht mehr beantwortet
  werden. Jederzeit wieder zu öffnen.
* **Verschieben** – Ein Thema in ein anderes Forum **desselben Startpunkts** verschieben (Auswahl
  des Zielforums).
* **Beiträge bearbeiten / löschen** – Fremde Beiträge korrigieren oder entfernen. Wird der erste
  Beitrag eines Themas gelöscht, wird das gesamte Thema mit allen Beiträgen, Umfragen und
  Meldungen entfernt.
* **Meldungen bearbeiten** – Von Mitgliedern gemeldete Beiträge erscheinen unter
  **„Mein Bereich → Meldungen"** (nur für das zuständige Team des jeweiligen Forums, inkl.
  Begründung und Verweis auf den Beitrag). Erledigte Meldungen werden per „Erledigt" geschlossen.

**Regel:** **Administratoren** dürfen all das immer. **Moderatoren** dürfen die einzelnen
Aktionen nur, wenn sie in den [globalen Einstellungen](einstellungen.md#rechte-der-moderatoren)
freigeschaltet sind.

---

## Massen-Moderation

In der **Forenansicht** (Themenliste) sehen Berechtigte an jedem Thema ein **Auswahlkästchen**
und unter der Liste eine **Aktionsleiste**. Mehrere markierte Themen lassen sich so in einem
Schritt bearbeiten:

| Aktion              | Benötigtes Moderatoren-Recht          |
|---------------------|----------------------------------------|
| **Schließen / Öffnen** | „Themen schließen"                  |
| **Verschieben**     | „Themen verschieben" (Ziel: ein anderes Forum desselben Startpunkts) |
| **Löschen**         | „fremde Beiträge bearbeiten/löschen"  |

Das **Löschen** wird zur Sicherheit ausdrücklich bestätigt und entfernt die Themen
**vollständig** – samt aller Beiträge, „Gefällt mir"-Markierungen, Umfragen, Abonnements,
Lesestände und Meldungen. Es werden nur Themen des jeweiligen Forums berücksichtigt; welche
Aktionen in der Leiste erscheinen, richtet sich nach den eigenen Rechten.

---

## Mitglieder sperren (Bann)

Wiederholte Störer lassen sich für das Forum **sperren**. Ein gesperrtes Mitglied kann weiterhin
**lesen**, aber **keine Themen mehr erstellen und nicht mehr antworten** (es sieht dann einen
entsprechenden Hinweis). Die Sperre gilt forumweit (für alle Startpunkte), da sie sich auf das
Contao-Mitglied bezieht.

* **Schnell sperren** – An jedem Beitrag steht Berechtigten die Schaltfläche **„Sperren"** zur
  Verfügung (sperrt den Verfasser nach einer Sicherheitsabfrage).
* **Verwalten** – Unter **„Mein Bereich → Sperren"** (nur für Berechtigte) erscheinen alle
  gesperrten Mitglieder mit Begründung, Sperrer und Datum sowie der Schaltfläche **„Freigeben"**.
  Dort lässt sich ein Mitglied auch gezielt über seinen **Benutzernamen** sperren (mit
  optionaler Begründung).

Das Sperren ist eine strenge Maßnahme: **Administratoren** dürfen es immer, **Moderatoren** nur,
wenn die Einstellung **„Moderatoren dürfen Mitglieder sperren"** aktiv ist (**Standard: aus**,
siehe [Globale Einstellungen](einstellungen.md#rechte-der-moderatoren)). Das eigene Konto lässt
sich nicht sperren.
