# Einstellungen je Knoten (Startpunkt, Kategorie, Forum)

[← Zurück zur Übersicht](../README.md)

Jeder Knoten (Startpunkt, Kategorie, Forum) hat – je nach Typ – folgende Einstellungen. **Fast
alle vererben sich nach unten:** Was am Startpunkt eingestellt ist, gilt für alle Kategorien
und Foren darunter, kann dort aber ergänzt werden.

## Bezeichnung und Beschreibung

* **Bezeichnung / Alias** – Der Name und die (automatisch erzeugte) URL-Kennung.
* **Beschreibung** – Ein kurzer Text, der im Frontend unter dem Forennamen erscheint.

## Forum-Icon

Ein **Lucide-Icon** je Bereich, wählbar über ein anklickbares Symbol-Raster (nach Kategorien
geordnet, u. a. echte Schachfiguren). Wird vererbt: Der Startpunkt gibt ein Standard-Icon vor,
Kategorie und Forum dürfen es überschreiben. Leer = erben.

## Geschlossen (nur Forum)

Ist ein Forum **geschlossen**, können darin keine neuen Themen oder Antworten mehr verfasst
werden; gelesen werden darf weiterhin.

## Zugriffsschutz (Mitglieder)

* **Zugriff schützen** – Aktiviert den Schutz für diesen Bereich und alles darunter.
* **Erlaubte Mitgliedergruppen** – Nur Mitglieder dieser Contao-Gruppen sehen den Bereich.

Ist ein Bereich nicht geschützt, ist er (Standard von Contao) öffentlich lesbar.

## Gäste-Zugriff

Für **nicht angemeldete Besucher** (Gäste) getrennt regelbar und ebenfalls vererbt:

* **Gäste dürfen lesen** – Öffentlicher Lesezugriff (auch für Mitglieder ohne passende Gruppe).
* **Gäste dürfen schreiben** – Erlaubt Gästen zusätzlich das Anlegen von Themen und Antworten.

> Hinweis: Ist die fiktive Gruppe „Gäste" bereits in den erlaubten Mitgliedergruppen
> ausgewählt, dürfen Gäste dort nur **lesen**; die Schreib-Checkbox bleibt dann ohne Wirkung.

**Ein geschützter Bereich bleibt geschützt:** Ist ein Knoten per Zugriffsschutz auf
Mitgliedergruppen beschränkt und lässt Gäste weder über die Gäste-Gruppe noch über seine
**eigenen** Checkboxen zu, ist sein gesamter Teilbereich **nicht öffentlich** – die
Gäste-Checkboxen anderer Knoten (egal ob über- oder untergeordnet) können diesen Schutz
nicht aufheben. Ein untergeordnetes Forum mit „Gäste dürfen lesen" (etwa aus einem
[Import](import.md)) öffnet also **keinen** geschützten Startpunkt; umgekehrt kann sich ein
Forum unterhalb eines offenen Bereichs jederzeit selbst auf Mitgliedergruppen beschränken.

## Administratoren und Moderatoren

Je Knoten lassen sich – über **Mitgliedergruppen** und/oder **einzelne Mitglieder** –
**Administratoren** und **Moderatoren** festlegen. Die einzelnen Mitglieder werden über ein
**Dialogfenster** ausgewählt (Schaltfläche neben dem Feld): Es zeigt die Mitgliederliste des
Backends mit **Suche und Blättern** und bleibt damit auch bei zehntausenden Mitgliedern
flüssig. Beide Rollen vererben sich nach unten. Was
Moderatoren dürfen, wird global in den [Einstellungen](einstellungen.md) geregelt
(Themen anpinnen, schließen, verschieben, Beiträge bearbeiten/löschen, Mitglieder sperren);
**Administratoren** dürfen dies immer. Details siehe [Moderation](moderation.md).

Am **Startpunkt** gibt es zusätzlich die Option **„Moderatoren im Frontend anzeigen"**: Ist sie
aktiv, erscheinen bei jedem Forum die Namen seiner Moderatoren (als Gruppe gewählte Moderatoren
werden dabei zu Einzelnamen aufgelöst).

## Umfragen erstellen

Je Knoten lässt sich – über **Gruppen** und/oder **einzelne Mitglieder** (Auswahl über das
Dialogfenster, wie bei den Rollen) – festlegen, wer beim Anlegen eines Themas eine
**Umfrage** erstellen darf. Auch dieses Recht vererbt sich nach
unten. Ist nirgends etwas vergeben, darf niemand Umfragen anlegen. Zur Bedienung siehe
[Frontend → Umfragen](frontend.md#umfragen).

## Veröffentlichung

Nur **veröffentlichte** Knoten sind im Frontend sichtbar. Ist ein übergeordneter Knoten
unveröffentlicht, ist der gesamte Bereich darunter unsichtbar.
