# Synapsis Changelog

## Version 1.5.0 (2026-07-26)

> Enthält Schema-Änderungen: nach dem Update `contao:migrate` ausführen (neue Felder `showModerators` in `tl_synapsis_forum` und `colorScheme` in den Einstellungen). Zusätzlich Assets neu veröffentlichen (CSS).

### Hinzugefügt

* **Moderatoren im Frontend anzeigen**: Neue Startpunkt-Einstellung „Moderatoren im Frontend anzeigen". Ist sie aktiv, erscheinen bei jedem Forum die Namen seiner Moderatoren (auch als Mitgliedergruppe gewählte Moderatoren werden zu Einzelnamen aufgelöst) – in der Übersicht, der Kategorie-Ansicht und im Forum-Kopf.
* **Farbschemata**: In den globalen Einstellungen lässt sich ein Farbschema wählen. Neben dem Standard (Blau) gibt es „Schachbund" (Petrol, orientiert an schachbund.de) und „BdF" (Gold, orientiert an bdf-fernschachbund.de). Die Schemata überschreiben nur die Farb-Variablen; eigene weitere Schemata lassen sich als CSS-Klasse `synapsis-scheme--<name>` ergänzen.

### Behoben / Geändert

* **Kategorie-Einzelansicht**: Die Überschrift (h2) wird jetzt korrekt formatiert (das CSS galt bisher nur für die h3 der Übersicht).
* **Spaltenausrichtung** in den Listen: `synapsis-col-last` ist nun in Ausrichtung und Farbe an `synapsis-col-count` angeglichen (Breite bleibt funktional unterschiedlich).

## Version 1.4.1 (2026-07-25)

> Keine Schema-Änderung gegenüber 1.4.0 – nach dem Update genügt `composer update` und ein Neuveröffentlichen der Assets (kein `contao:migrate` nötig).

### Geändert / Behoben

* **Beitrags-Aktionen einheitlich als Buttons**: „Zitieren", „Bearbeiten", „Melden" und „Löschen" werden jetzt durchgängig als Buttons (gleiche Pill-Optik wie „Gefällt mir") dargestellt – vorher waren einige davon Textlinks. Das Löschen-Layout ist damit ebenfalls sauber.
* **Umfrageende mit Uhrzeit**: Das Umfrageende lässt sich jetzt auf **Datum und Uhrzeit** genau festlegen (Feld `datetime-local`). Ohne Uhrzeitangabe gilt weiterhin das Ende des gewählten Tages.
* **Kategorien in der Pfadnavigation verlinkt**: Kategorien in den Brotkrumen sind jetzt anklickbar und öffnen eine **Einzelansicht der Kategorie** (nur ihre Foren) über `?category=<id>`.
* **„Mein Bereich"** trägt jetzt einen Doppelpunkt und ist farblich hervorgehoben.

### Hinweis

* Die Alt-Spalte `option` in `tl_synapsis_poll_vote` (aus dem früheren Umfrage-Umbau) ist ungenutzt und wird von `contao:migrate` nicht automatisch entfernt – sie kann ignoriert werden.

## Version 1.4.0 (2026-07-25)

> Enthält Schema-Änderungen: nach dem Update `contao:migrate` ausführen (neue Tabellen `tl_synapsis_report`, `tl_synapsis_forum_sub`, `tl_synapsis_notification`; Felder `editedAt`/`editedBy` in `tl_synapsis_post`; neue Moderations- und Team-Benachrichtigungs-Felder in den Einstellungen).

### Hinzugefügt – Beiträge & Moderation

* **Beiträge bearbeiten und löschen**: Mitglieder dürfen ihre **eigenen** Beiträge bearbeiten/löschen, solange das Thema offen ist; nach dem Bearbeiten erscheint ein Hinweis „Zuletzt bearbeitet von … am …". Moderatoren/Administratoren dürfen fremde Beiträge bearbeiten/löschen (durch Einstellung geregelt). Beim Löschen des ersten Beitrags wird das ganze Thema samt Umfrage und Meldungen entfernt.
* **Zitieren**: „Zitieren" übernimmt den Beitrag als Zitat ins Antwortfeld; die zitierte Person wird benachrichtigt.
* **Thema schließen/öffnen und verschieben**: Neue Moderationsaktionen in der Themenansicht (Verschieben nur innerhalb desselben Startpunkts).
* **Beiträge melden**: Mitglieder können Beiträge mit Begründung der Moderation melden. Das zuständige Team sieht offene Meldungen unter „Mein Bereich → Meldungen" und schließt sie mit „Erledigt" ab.
* **Erweiterte Moderatoren-Rechte**: Die globalen Einstellungen steuern nun getrennt, ob Moderatoren anpinnen, schließen, verschieben und Beiträge bearbeiten/löschen dürfen (Administratoren immer).

### Hinzugefügt – Benachrichtigungen

* **Benachrichtigungscenter**: Neuer Menüpunkt „Benachrichtigungen" in „Mein Bereich" mit Ungelesen-Zähler (Badge). Anlässe: Antwort auf das eigene Thema, Zitat des eigenen Beitrags, `@Erwähnung` sowie neue Meldungen (für das Team). Startpunkt-gebunden wie alle Zähler.
* **@Erwähnungen**: `@Benutzername` im Beitrag benachrichtigt das genannte Mitglied.
* **Forum abonnieren**: Zusätzlich zum Themen-Abo lässt sich ein ganzes Forum abonnieren – E-Mail an die Abonnenten bei jedem neuen Thema.
* **Team-Benachrichtigung per E-Mail**: Administratoren und/oder Moderatoren eines Forums können bei neuen Themen, Antworten oder beidem per E-Mail informiert werden (mit eigener Betreff-/Text-Vorlage; Platzhalter `##forum##`, `##topic##`, `##author##`, `##url##`).

### Geändert

* Das Handbuch (README) wurde um die Abschnitte **Moderation** und **Benachrichtigungen** erweitert; die globalen Einstellungen sind vollständig dokumentiert.

## Version 1.3.1 (2026-07-25)

### Behoben

* **Suche findet jetzt auch Umfragen**: Die Forensuche durchsucht zusätzlich zur Themen- und Beitragsebene die **Umfrage-Frage und die Antwortmöglichkeiten**. Ein Thema wird also auch gefunden, wenn der Suchbegriff nur in seiner Umfrage vorkommt. (Keine Schema-Änderung.)

### Geändert

* Bei Umfrageergebnissen heißt es jetzt „%d Teilnehmer" statt „Teilnehmende".

## Version 1.3.0 (2026-07-24)

> Enthält Schema-Änderungen: nach dem Update `contao:migrate` ausführen (neue Umfrage-Tabellen, Rollen-/Umfrage-Felder in `tl_synapsis_forum` sowie `modCanPin` in den Einstellungen).

### Hinzugefügt

* **Administratoren und Moderatoren**: An Startpunkt, Kategorie und Forum lassen sich – über **Mitgliedergruppen** und/oder **einzelne Mitglieder** – Administratoren und Moderatoren festlegen. Die Rollen vererben sich nach unten (Logik in der testbaren Klasse `Frontend\RoleAccess`).
* **Themen anpinnen**: Administratoren (immer) und Moderatoren können Themen oben anpinnen bzw. wieder lösen (Button in der Themenansicht; nutzt das vorhandene „angeheftet"). Ob **Moderatoren** anpinnen dürfen, legt die neue globale Einstellung „Moderatoren dürfen Themen anpinnen" fest – so lassen sich die Moderatoren-Rechte später erweitern.
* **Umfragen**: Beim Anlegen eines Themas kann optional eine Umfrage erstellt werden – wahlweise **Einfachauswahl** (eine Antwort) oder **Mehrfachauswahl**. Ein **Umfrageende** (Datum) ist Pflicht; danach kann nicht mehr abgestimmt werden. Optional lässt sich festlegen, dass die **Ergebnisse erst nach dem Umfrageende** sichtbar werden (sonst direkt nach der eigenen Stimmabgabe). Nach dem Ende sind die Ergebnisse immer sichtbar. Angezeigt werden Balken, Prozent, Teilnehmerzahl und der Status (läuft bis / beendet am). Doppelabstimmung ist ausgeschlossen.
* **Umfragen-Erstellrecht (vererbt)**: Wer Umfragen anlegen darf, wird pro Startpunkt, Kategorie oder Forum über **Mitgliedergruppen** und/oder **einzelne Mitglieder** vergeben und vererbt sich nach unten (Standard: niemand). Logik in den testbaren Klassen `Frontend\PollAccess` und `Frontend\PollManager`.

### Behoben

* **BB-Code in Signaturen wurde nicht geparst**: Contao maskiert beim Speichern Sonderzeichen wie `=` und `#` als HTML-Entities (`&#61;`, `&#35;`), wodurch `[url=…]`/`[color=#…]` im Frontend als `[url&#61;…]` erschienen statt als Link/Farbe. `BBCode::toHtml` wandelt die Entities jetzt zuerst zurück (und maskiert danach wieder sicher); zusätzlich werden Insert-Tags (`{{…}}`) in Signaturen neutralisiert. Bereits gespeicherte Signaturen werden dadurch ebenfalls korrekt dargestellt. Im Bearbeiten-Feld erscheint der BB-Code wieder lesbar.

## Version 1.2.0 (2026-07-24)

> Keine Schema-Änderung gegenüber 1.1.0 – nach dem Update genügt `composer update` und ein Neuveröffentlichen der Assets (kein `contao:migrate` nötig).

### Behoben / Geändert

* **Zähler bleiben im Startpunkt**: Der Autor-Beitragszähler unter den Beiträgen zählte bisher über alle Startpunkte hinweg. Jetzt zählt er nur die Beiträge innerhalb des angezeigten Startpunkts. (Statistiken, Gelesen-/Ungelesen-Ermittlung, Themen-/Beitragslisten waren bereits startpunkt-gebunden.)
* **„Mein Bereich" auf jeder Seite**: Die untere Navigationsbox erscheint jetzt auf allen Forenseiten (vorher nur auf der Übersicht). Der aktuell geöffnete Menüpunkt wird als Text statt als Link dargestellt.

### Hinzugefügt

* **„Gefällt mir" in „Mein Bereich"**: Neuer Menüpunkt mit eigener Seite, die die Themen auflistet, in denen das Mitglied Beiträge mit „Gefällt mir" markiert hat (auf den Startpunkt beschränkt).
* **Forensuche**: Eine Suchbox auf Übersicht, Forum- und Themenansicht durchsucht Themen-Titel und Beitragstexte **innerhalb des Startpunkts** und zeigt die Treffer als Liste. Nur lesbare Foren werden durchsucht.
* **BB-Code in Signaturen**: In der Signatur sind jetzt `[b]`, `[i]`, `[u]`, `[s]`, `[url]` und `[color]` erlaubt und werden unter den Beiträgen als HTML dargestellt. Über dem Signaturfeld gibt es **Einfüge-Buttons** (B, I, U, S, Link, Farbe), die den markierten Text umschließen – man muss den Code nicht auswendig kennen. Die Umwandlung ist XSS-sicher (erst maskieren, dann nur wohlgeformte Marken ersetzen; URLs nur mit http(s)://). Logik in `Frontend\BBCode`.

## Version 1.1.0 (2026-07-24)

> Enthält Schema-Änderungen: nach dem Update `contao:migrate` ausführen (neue Tabellen `tl_synapsis_read`, `tl_synapsis_like`, `tl_synapsis_settings` sowie die Spalte `tl_member.signature`).

### Hinzugefügt

* **Autorname wird gespeichert** (`authorName` in `tl_synapsis_topic` und `tl_synapsis_post`): Beim Schreiben eines Themas/Beitrags wird der Benutzername als Momentaufnahme mitgespeichert. Existiert das Konto später nicht mehr, wird der Autor als **„Gast (früherer Benutzername)"** angezeigt statt als „Unbekannt". Das gilt auch für Importe aus Fremdsystemen (Autor-ID 0 mit ursprünglichem Namen). Die Anzeigelogik steckt in der testbaren Klasse `Frontend\AuthorLabel` (7 Unit-Tests).
* **CSV-Import aus zwei Dateien**: Eine Datei mit **Kategorien/Foren** (Struktur) und eine mit **Themen/Beiträgen** (Inhalt). Themen verweisen über die Forum-Referenz auf die Struktur, Beiträge über die Themen-Referenz auf ihr Thema – passt zu Fremdsystem-IDs (z. B. phpBB `forum_id`/`topic_id`). Die Inhalt-Datei ist optional; Themen unter Kategorien und verwaiste Beiträge werden übersprungen. Der Import läuft in einer Transaktion (Rollback bei Fehler).
* **Pfadnavigation als Box**: Die Brotkrumen stehen jetzt als abgesetzte Box oben – auch auf der Übersicht.
* **Gelesen-Funktion**: Für angemeldete Mitglieder wird der Lesestand je Thema gemerkt (neue Tabelle `tl_synapsis_read`). Ungelesene Themen und Foren werden in den Listen markiert (fetter Titel + Punkt). Beim Öffnen eines Themas gilt es als gelesen. Die Logik steckt in der testbaren Klasse `Frontend\ReadTracker`.
* **Mitglieder-Bereich (untere Box)**: Für angemeldete Mitglieder eine Navigationsbox mit vier Unteransichten – **Meine Beiträge**, **Ungelesene Beiträge**, **Abonnements verwalten** (mit Abbestellen) und **Signatur bearbeiten**. Die Forensignatur (`tl_member.signature`) wird unter den Beiträgen angezeigt.
* **„Gefällt mir" für Beiträge**: Angemeldete Mitglieder können Beiträge liken und wieder entliken (nicht den eigenen). Unter jedem Beitrag stehen Anzahl und Namen der Likenden (neue Tabelle `tl_synapsis_like`, Logik in `Frontend\LikeManager`).
* **Smileys**: Eine Smiley-Leiste unter dem Editor fügt gängige Emoji per Klick ein (mit TinyMCE und einfacher Textarea). Zusätzlich steht im TinyMCE der Emoji-Auswahl-Button zur Verfügung.
* **Avatare (optional)**: Ist [terminal42/contao-avatar](https://github.com/terminal42/contao-avatar) installiert, wird der Mitglieder-Avatar verwendet; sonst das bisherige farbige Standard-Icon (Lucide). Die Markup-Logik steckt in `Frontend\AvatarResolver`.
* **Globale Einstellungen (Backend)**: Neues Backend-Modul „Einstellungen" in der Synapsis-Gruppe (Einzelsatz `tl_synapsis_settings`). Dort lassen sich die **E-Mail-Vorlagen** für die Benachrichtigung bei neuen Antworten pflegen (Platzhalter `##topic##`, `##name##`, `##url##`), die Benachrichtigung global an-/abschalten und ein optionaler Absender (Name/E-Mail) festlegen. Die Vorlagen nutzen Token statt `sprintf` (robust gegen `%`-Zeichen; `Frontend\NotificationTemplate`).

### Entfernt

* **CSV-Export**: Das Modul „CSV Import / Export" heißt jetzt „CSV Import" und dient nur noch dem Import. (Auf Wunsch – der Fokus liegt auf der Datenübernahme aus Fremdsystemen.)

### Verifiziert

* Auf Contao 4.13.58 und 5.7.7: Schema-Migration (`tl_synapsis_read`, `tl_synapsis_like`, `tl_synapsis_settings`, `tl_member.signature`), Zwei-Datei-Import inkl. `authorName` und Guards, Import-Formular (echtes Rendering), Gelesen-Funktion (ungelesen → gelesen → neuer Beitrag), „Gefällt mir" (Toggle, kein Selbst-/Gast-Like, mehrere Liker), globale Einstellungen (Einzelsatz anlegen, Vorlagen-Pipeline, Backend-Modul), Rendering aller vier Panel-Unteransichten und der Smiley-Leiste, Themenerstellung, Frontend-Rauchtest, 34 Unit-Tests.
* Der Avatar-Fallback (Lucide) und die Markup-Logik sind unit-getestet; die Anbindung an terminal42/contao-avatar ist nach dessen Insert-Tag-API implementiert, aber lokal nicht verifiziert (Bundle dort nicht installiert) – bei Bedarf auf Staging prüfen.

## Version 1.0.1 (2026-07-24)

### Behoben

* **Kategorie-Überschriften im Frontend lesbar**: Die Überschrift auf dem farbigen Kategorie-Kopf erbte nur das Weiß des Kopfes und wurde von einer direkten `h3`-Regel des Website-Themes überschrieben (Text kaum sichtbar). Die Schriftfarbe wird jetzt direkt auf der Überschrift und der Beschreibung gesetzt (`color: #fff !important`) und überschreibt das Theme zuverlässig.

## Version 1.0.0 (2026-07-24)

**Erstes stabiles Release – Produktivbetrieb.**

### Behoben

* **Hilfetext im CSV-Import korrekt ausgerichtet**: Der Hinweis unter dem Ziel-Feld saß am linken Rand statt bündig zum Feld. Er steht jetzt innerhalb des `.widget`-Containers und richtet sich wie ein normaler Feld-Hilfetext aus.

### Hinzugefügt

* **Icon für den Backend-Bereich „Synapsis-Forum"**: Die eigene Navigationsgruppe erhält ein Lucide-Icon (`messages-square`). Es wird als CSS-Maske über `currentColor` eingefärbt und übernimmt damit automatisch die Textfarbe der Navigation – sichtbar sowohl in der dunklen Contao-4.13-Navigation als auch im hellen und dunklen Contao-5-Theme. Das Backend-Stylesheet wird nur im Backend-Scope geladen.

### Verifiziert

* Auf Contao 4.13.58 und 5.7.7: BE_MOD-Registrierung, scope-abhängiges Laden des Backend-CSS, CSV-Roundtrip, sitzungsbasierte Zähler-Sperre, Themenerstellung, Frontend-Rauchtest, 15 Unit-Tests.

## Version 0.5.1 (2026-07-24)

### Geändert

* **Eigener Backend-Bereich „Synapsis-Forum"**: Da es nun zwei Module gibt, liegen „Forum" und „CSV Import / Export" in einer eigenen Backend-Gruppe statt unter „Inhalte". (Modulschlüssel `synapsis_forum` und `synapsis_csv`.)
* **Absende-Buttons im CSV-Modul korrekt ausgerichtet**: Die Buttons stehen jetzt im Contao-Standardcontainer (`tl_formbody_submit`) statt am linken Rand.

## Version 0.5.0 (2026-07-24)

### CSV-Export und -Import (Sicherung & Wiederherstellung)

* Neues Backend-Modul **„Synapsis CSV Import / Export"** (Gruppe Inhalte).
* **Export**: Ein Startpunkt wird mit seiner kompletten Unterstruktur – Kategorien, Foren (inkl. Icon, Schutz, Gruppen, Gäste-Freigaben), Themen und Beiträge – in eine CSV-Datei geschrieben.
* **Import**: Die CSV wird unter ein wählbares Ziel eingebunden – ein **Startpunkt** (erwartet Kategorien) oder eine **Kategorie** (erwartet Foren). Der Baum wird über laufende Referenznummern eindeutig wiederhergestellt (unabhängig von den ursprünglichen IDs). Damit lässt sich eine **vorher gelöschte Struktur vollständig wiederherstellen** (per Roundtrip-Test auf Contao 4.13 und 5.7 verifiziert).

### Geändert

* **Ansichtszähler mit Sperre**: Ein Reload zählt nicht mehr mit. Statt IP-Adressen (personenbezogen, DSGVO) merkt sich das Forum in der **Sitzung**, welche Themen schon gezählt wurden – pro Sitzung wird ein Thema nur einmal gezählt, ein neuer Besuch wieder. Kein IP-Speicher, keine Aufbewahrungsfrist.
* **Icon-Auswahl auf 221 Icons erweitert** – darunter jetzt **echte Schachfiguren** (König, Dame, Turm, Läufer, Springer, Bauer) sowie Schachbrett-Raster, Würfel und Sanduhr in der Kategorie „Schach & Spiel".

### Verifiziert

* Auf Contao 4.13.58 und 5.7: CSV-Roundtrip (Export → Löschen → Import → vollständige Wiederherstellung), sitzungsbasierte Zähler-Sperre, Backend-Modul-Registrierung, Themenerstellung, Frontend-Rauchtest, 15 Unit-Tests.

## Version 0.4.2 (2026-07-24)

### Behoben

* **„Thema erstellen" löste keinen Request aus** (mit aktivem TinyMCE): Das Textfeld war als `required` markiert, wird von TinyMCE aber ausgeblendet. Ein verstecktes Pflichtfeld verhindert die native Formular-Absendung („not focusable"), ohne Fehlermeldung – daher passierte beim Klick nichts (der „Abbrechen"-Link war als Navigation nicht betroffen). Das `required` am Textfeld ist entfernt; leere Eingaben werden weiterhin serverseitig abgefangen. Gilt für das Antwortformular und das Formular für neue Themen.

### Geändert

* **Icon-Auswahl nach Bereichen gruppiert** und auf **211 Icons** erweitert (12 Bereiche: Kommunikation, Struktur & Ordner, Menschen, Symbole & Status, Freizeit & Medien, Natur & Wetter, Technik, Sport, Reisen & Orte, Wissen & Bildung, Handel & Zeit, Schach & Spiel). Direkt aus dem offiziellen Lucide-Sprite erzeugt.
* Das Icon-Raster **füllt jetzt die volle Breite** (vorher bei ~60 % abgeschnitten).

### Verifiziert

* Auf Contao 4.13.58 und 5.7: Themenerstellung per POST, Backend-Diagnose (Icon-Wizard mit 211 Icons in Bereichen), Frontend-Rauchtest, 15 Unit-Tests.

## Version 0.4.1 (2026-07-24)

### Behoben

* **Neue Themen wurden nicht gespeichert**: Nach Klick auf „Thema erstellen" passierte nichts. Ursache: Das Formular sendete `FORM_SUBMIT=synapsis_topic_…`, der Handler prüfte aber gegen `tl_synapsis_topic_…` – die Abfrage schlug immer fehl. Jetzt korrigiert; das Anlegen von Themen (inkl. erstem Beitrag) ist per POST-Test auf Contao 4.13 und 5.7 verifiziert.

### Geändert

* **Forum-Icons: visuelle Auswahl.** Unter dem Auswahlfeld erscheint jetzt ein anklickbares Raster mit allen Icons als Grafik – man muss also nicht mehr aus dem Namen auf das Aussehen schließen. Ein Klick übernimmt das Icon.
* **Deutlich mehr Icons**: Das kuratierte Lucide-Set wurde von 24 auf 54 erweitert (u. a. Liste, Ordner, Etikett, Mail, Link, Rakete, Ziel, Zahnrad, Kaffee, Gamepad, Schlüssel, Auge, Suche, Herz, Geschenk, Sonne, Mond …).

### Verifiziert

* Auf Contao 4.13.58 und 5.7: Themenerstellung per POST (Redirect + gespeichertes Thema + erster Beitrag), Backend-Diagnose (Icon-Wizard rendert 54 Icons), Frontend-Rauchtest, 15 Unit-Tests.

## Version 0.4.0 (2026-07-24)

### Gäste-Zugriff überarbeitet (Gäste-Gruppe + Checkboxen)

* **Fiktive Gruppe „Gäste" (-1) wieder in den erlaubten Mitgliedergruppen** – behebt die Anzeige „Unbekannte Option: -1" bei bestehenden Datensätzen (Wiederherstellung des Contao-Standards).
* **Vorrang-Regel**: Ist die Gruppe „Gäste" für einen geschützten Bereich ausgewählt, gewährt sie Gästen **Lesezugriff** (nie Schreibzugriff) und hat **Vorrang** vor den beiden Checkboxen – die bleiben dann ohne Wirkung.
* Die Checkboxen „Gäste dürfen lesen"/„Gäste dürfen schreiben" greifen dort, wo die Gäste-Gruppe **nicht** ausgewählt ist. „Gäste dürfen lesen" macht einen Bereich öffentlich lesbar (auch für Mitglieder ohne passende Gruppe), „Gäste dürfen schreiben" erlaubt zusätzlich das Schreiben.
* Damit ist ein reiner Gäste-Lesezugriff sicher möglich, ohne versehentlich Schreibrechte zu vergeben.

### Konfigurierbare Forum-Icons (Lucide)

* Neues Feld **„Forum-Icon"** auf Startpunkt, Kategorie und Forum mit einer kuratierten Auswahl von [Lucide](https://lucide.dev)-Icons.
* **Vererbung**: Im Startpunkt legt man das Standard-Icon für die Foren fest; Kategorie und Forum können es überschreiben (leer = erben). Im Frontend wird das effektive Icon als Inline-SVG dargestellt.

### Frontend-Korrekturen

* **Buttons** vereinheitlicht: `<a>`- und `<button>`-Buttons haben jetzt dieselbe Höhe/Ausrichtung; der „Abbrechen"-Button erscheint als klar erkennbarer Sekundär-Button (gleiche Form wie „Thema erstellen", andere Farbe).
* **TinyMCE**: Der Basis-Pfad ist jetzt **absolut**. Im Vorschau-Modus (`preview.php`) wurde ein relativer Pfad zuvor zu `preview.php/assets/…` aufgelöst, wodurch Skins/Plugins per 404 fehlschlugen (nicht das fehlende SSL-Zertifikat war die Ursache).

### Verifiziert

* Auf Contao 4.13.58 **und** 5.7: Migration `forumIcon`, Backend-Diagnose (Gäste-Gruppe in den Optionen, Felder vorhanden, Lucide-Icons), Frontend-Rauchtest (Lesezone/Schreibzone/Geheim + Gäste-Gruppe mit Vorrang + Leak-Test); 15 Unit-Tests der Zugriffslogik.

## Version 0.3.0 (2026-07-24)

### Neues Gäste-Zugriffsmodell (Opt-in, Lesen/Schreiben getrennt)

* Zwei getrennte, im Baum vererbte Checkboxen je Startpunkt/Kategorie/Forum: **„Gäste dürfen lesen"** und **„Gäste dürfen schreiben"** (schließt Lesen ein). Beide standardmäßig **aus** – vergessene Einstellung hält einen Bereich privat (fail-safe).
* Ersetzt die bisherige Lösung über die fiktive Gruppe `-1` im Mitgliedergruppen-Feld. Das Feld **Mitgliedergruppen** führt jetzt wieder ausschließlich echte Contao-Gruppen; Gäste-Rechte laufen getrennt über die beiden Checkboxen.
* Lese- und Schreibrecht für Gäste sind damit **unabhängig** steuerbar: z. B. ein ansonsten mitgliederpflichtiges Board für Gäste nur lesbar machen, ohne ihnen das Schreiben zu erlauben.
* „Gäste dürfen lesen" bedeutet **öffentlich lesbar**: auch angemeldete Mitglieder ohne passende Gruppe dürfen dann lesen (aber nicht schreiben).
* **Kein Datenleck mehr**: „Neueste Themen" und die Statistik berücksichtigen jetzt den Lesezugriff pro Forum – Themen/Beiträge aus gesperrten Foren tauchen dort nicht mehr auf.

### Frontend-Korrekturen

* **Button-Text war unlesbar** (dunkel auf dunkel, nur beim Mouseover blass sichtbar, unterstrichen): Die allgemeine Link-Formatierung der Website übertrumpfte die Button-Farbe. Die Button-Stile sind jetzt gezielt qualifiziert (Element + Klasse), ohne Unterstreichung, mit lesbarer Schriftfarbe.
* **„Abbrechen"-Button** wird jetzt als eigenständiger Sekundär-Button dargestellt (gleiche Form wie „Thema erstellen", andere Farbe) statt als Link.
* **TinyMCE-Versions-Schutz**: Der Editor initialisiert nur noch mit TinyMCE 4+. Ist auf der Seite ein altes TinyMCE 3 aktiv (z. B. durch das parallel installierte `contao/forum-bundle`), wird der Editor nicht mehr fehlerhaft eingebunden. Hinweis: Der in der Konsole gemeldete Pfad `bundles/contaoforum/...` stammt von jenem Fremd-Bundle, nicht von Synapsis.

### Verifiziert

* Auf Contao 4.13.58 **und** 5.7: Migration der Felder `guestRead`/`guestWrite`, Backend-Diagnose (Felder vorhanden, Gruppen-Optionen ohne `-1`), Frontend-Rauchtest (Lesezone: lesen ohne schreiben; Schreibzone: lesen und schreiben; geheimes Forum kein Zugriff; kein Leak geheimer Themen); 14 Unit-Tests der Zugriffslogik.

## Version 0.2.3 (2026-07-24)

### Behoben

* **„Unbekannte Option: forum" in der Typ-Auswahl** beim Anlegen einer Kategorie/eines Forums: Ein neuer Datensatz startete mit dem SQL-Standardwert `forum`, der an der jeweiligen Position gar nicht erlaubt ist. Ein neuer `oncreate_callback` belegt den Typ jetzt passend zur Position vor (oberste Ebene → `root`, im Startpunkt → `category`, in der Kategorie → `forum`).
* **Frontend-Icons wurden von einer Icon-Schrift überlagert** (z. B. der Text „message-square"): Statt eines Icon-Namens im `data-icon`-Attribut (das fremde Icon-Systeme der Seite interpretieren) wird das Forum-Icon nun als fertiges Inline-SVG (Sprechblase bzw. Schloss) ausgegeben.

### Geändert

* **Gäste dürfen schreiben**: Wer ein Forum sehen darf (auch die fiktive Gruppe „Gäste"), sieht in nicht geschlossenen Foren jetzt den „Neues Thema"-Button und das Antwortformular – vorher nur angemeldete Mitglieder. Beiträge von Gästen werden dem Autor „Gast" zugeordnet. Themen abonnieren bleibt angemeldeten Mitgliedern vorbehalten.

### Verifiziert

* Auf Contao 4.13.58 **und** 5.7: Backend-Diagnose (Typ-Vorbelegung via oncreate, Operationen/Toggle je Version, Gäste-Gruppe) und Frontend-Rauchtest (Gäste-Zugriff, „Neues Thema"/Antwort als Gast, Ansichtszähler) grün; 13 Unit-Tests.

## Version 0.2.2 (2026-07-24)

### Geändert

* **Backend-Modul-Schlüssel von `forum` auf `synapsis` umbenannt** (Aufruf jetzt `do=synapsis`). Der bisherige Schlüssel `forum` kollidierte mit anderen Forum-Bundles (z. B. dem älteren Diskussionsforum), die im Backend ebenfalls `do=forum` verwenden – dadurch war unklar, welches Modul geöffnet wird.
* **Backend-Modul in die Kern-Gruppe „Inhalte" (`content`) verschoben** – es wird also keine eigene Backend-Gruppe „Synapsis-Forum" mehr angelegt. Das Modul erscheint als „Synapsis-Forum" unter Inhalte.
* Auf Contao 4.13.58 **und** 5.7 verifiziert (Registrierung unter `content/synapsis`, kein `do=forum`, Label „Synapsis-Forum").

## Version 0.2.1 (2026-07-24)

### Behoben

* **Fataler Fehler bei der Installation unter Contao 4.13** (`DcaLoader: Cannot access offset of type string on string` beim `cache:warmup`). Ursache: Die Contao-Version wurde über `method_exists(DataContainer::class, 'getDriverForTable')` erkannt – diese Methode existiert aber bereits in Contao 4.13, sodass dort fälschlich der Contao-5-Zweig mit String-Referenz-Operationen (`'edit'`, `'copy'`, …) griff. Die Erkennung läuft jetzt zuverlässig über die installierte Paketversion (`SchachbulleContaoSynapsisBundle::isContao5()`), betrifft alle vier DCA-Dateien (Treiberklasse) und die Operationsleiste der Forenstruktur.

## Version 0.2.0 (2026-07-24)

### Frontend-Modul

* Neues Frontend-Modul „Forum" (`synapsis_forum`, Modulgruppe „Synapsis-Forum") als Legacy-Modul (`extends \Contao\Module`) – läuft unter Contao 4.13 und Contao 5
* Modul-Einstellungen in `tl_module`: Startpunkt-Auswahl (Options-Callback über getaggten Service), Einträge pro Seite, TinyMCE-Editor an/aus, Dateianhänge an/aus samt Upload-Verzeichnis
* Eine Modulinstanz stellt alle Ansichten über URL-Parameter dar (mybb-Prinzip): Übersicht, Forum-Themenliste (`?forum=`), Thema mit Beiträgen (`?topic=`), Formular für neues Thema (`?forum=…&new=1`)
* **Übersicht**: Kategorien mit ihren Foren (Icon, Beschreibung, Themen-/Beitragszähler, letzter Beitrag), Block „Neueste Themen" darüber, Statistiken darunter (Themen, Beiträge, aktivste Mitglieder)
* **Forumansicht**: Unterforen und Themenliste seitenweise (angeheftete zuerst, dann nach Datum), Angeheftet-/Geschlossen-Badges, Brotkrumen-Navigation
* **Themenansicht**: Beiträge seitenweise mit Autor, Avatar, Datum und Beitragszahl; Ansichtszähler wird erhöht; Antwortformular am Ende
* **Zugriffsschutz** wird zur Laufzeit vererbt ausgewertet: geschützte/unveröffentlichte Bereiche werden anhand der Contao-Mitgliedergruppen ein- oder ausgeblendet (Klasse `Frontend\ForumAccess`, isoliert unit-getestet)
* Themen anlegen und Antworten schreiben für angemeldete Mitglieder; TinyMCE-Editor (aus den Contao-Assets) mit Emoticons; optionale Dateianhänge (`FileUpload` → UUIDs), Bilder werden inline dargestellt
* Standard-Avatar je Mitglied als Lucide-Icon mit aus der Mitglieds-ID abgeleiteter Farbe
* Vier `.html5`-Templates und ein schlankes, responsives Stylesheet im klassischen Forenlayout (Kategorie-Kopfzeilen, Zeilen mit Icon/Zähler/letztem Beitrag)
* Deutsche Frontend-Texte in `languages/de/default.php`

### Backend-Korrekturen und Erweiterungen (Review-Runde)

* **Typ-Auswahl korrigiert**: Der Options-Callback lieferte ein numerisch indiziertes Array, wodurch Contao statt des Typnamens den Schlüssel (0/1) speicherte – dadurch griff die leere Default-Palette, der Startpunkt ließ sich nicht bearbeiten und weder Typwahl noch Gruppen erschienen. Jetzt assoziativ (`Wert => Label`)
* **Strenge Hierarchie** durchgesetzt: Startpunkt nur auf oberster Ebene, Kategorie nur im Startpunkt, Forum nur in einer Kategorie, Themen nur im Forum
* **Veröffentlichungs-Toggle** in der Baumstruktur (Contao 5 nativ über `'toggle' => true`; Contao 4.13 als eigene Toggle-Operation) – unveröffentlichte Knoten werden mit gedämpftem Icon dargestellt
* **Gruppen-/Zugriffsauswahl** auf jeder Ebene inklusive der fiktiven Gruppe **Gäste** (`-1`); die Rechte werden im Baum vererbt
* **Gäste-Zugriff**: nicht angemeldete Besucher gelten als Gruppe `-1` und sehen ungeschützte Bereiche sowie geschützte Bereiche, die der Gruppe Gäste zugeordnet sind
* **Themen-Abonnements**: angemeldete Mitglieder können Themen abonnieren (Tabelle `tl_synapsis_subscription`); bei einer neuen Antwort werden die Abonnenten (außer dem Verfasser) per E-Mail benachrichtigt

### Verifiziert

* Gegen die Contao-5.7-Referenzinstallation: Migration der `tl_module`- und `tl_synapsis_subscription`-Tabellen, Callback-Registrierung im Container, Backend-Diagnose (Operationen inkl. nativem Toggle, assoziative Typ-Optionen, Gäste-Gruppe), Render-Rauchtest aller Leseansichten inkl. Gäste-Zugriff, Zugriffsschutz und Ansichtszähler
* 13 Unit-Tests (Strukturregeln + Zugriffsvererbung inkl. Gäste) grün

### Offen / nächste Schritte

* Schreib-Pfad (Thema/Antwort anlegen, Upload, Abo-Umschaltung, Benachrichtigungs-E-Mail) ist nur code-/API-geprüft, noch nicht mit echtem Login live getestet
* TinyMCE-integrierter Bild-Upload (derzeit Bild-Einbindung über die Dateianhänge)
* Emoticon-Auswahl im Editor feinjustieren; Zitieren/Melden von Beiträgen; RSS

## Version 0.1.0 (2026-07-23)

### Grundgerüst

* Bundle-Grundgerüst `schachbulle/contao-synapsis-bundle` (Typ `contao-bundle`) für **Contao 4.13 und Contao 5** (`^4.13 || ^5.0`, PHP `^7.4 || ^8.0`)
* Haupt-Bundle-Klasse, Contao-Manager-Plugin (automatische Erkennung) und DependencyInjection-Extension
* `services.yaml` mit Autowiring/Autoconfiguration; DCA-Callbacks werden über den Service-Tag `contao.callback` registriert (bewusst als YAML-Tag statt PHP-Attribut, damit PHP 7.4 unterstützt bleibt)
* Models `SynapsisForumModel`, `SynapsisTopicModel`, `SynapsisPostModel` im Namespace `Schachbulle\ContaoSynapsisBundle\Model`, registriert über `$GLOBALS['TL_MODELS']`

### Datenmodell

* `tl_synapsis_forum` – die komplette Forenstruktur in **einer** Baumtabelle (Sortier-Modus 5, analog zur Seitenstruktur). Das Feld `type` bestimmt die Rolle eines Knotens:
  * `root` = Startpunkt (eigenständige Forenstruktur, nur auf oberster Ebene)
  * `category` = Kategorie (gruppiert Foren, enthält selbst keine Themen)
  * `forum` = Forum (enthält Themen, Unterforen möglich)
* `tl_synapsis_topic` – Themen eines Forums (`pid`/`ptable` → `tl_synapsis_forum`), Elternansicht (Modus 4), angeheftete zuerst, danach nach Datum absteigend
* `tl_synapsis_post` – Beiträge eines Themas (`pid`/`ptable` → `tl_synapsis_topic`), chronologische Elternansicht
* Autor von Themen und Beiträgen ist ein **Contao-Mitglied** (`tl_member`), nicht ein Backend-Benutzer – die Benutzerverwaltung läuft vollständig über Contao

### Backend-Modul

* Modulgruppe „Synapsis-Forum" mit dem Modul „Forum" (`BE_MOD`) inklusive Icon; verwaltet alle drei Tabellen
* Typ-Regeln der Struktur werden erzwungen: oberste Ebene nur `root`, im Startpunkt `category`/`forum`, in der Kategorie nur `forum`, im Forum nur Unterforen (`ForumListener::getTypeOptions`)
* Eigene Baumdarstellung mit Icon je Typ; unveröffentlichte Knoten ausgegraut, geschlossene Foren markiert
* Schaltfläche „Themen" nur bei Foren sichtbar – Kategorien und Startpunkte können keine Themen enthalten
* Automatische Alias-Erzeugung aus Bezeichnung bzw. Titel inklusive Eindeutigkeitsprüfung (Foren und Themen)
* Zugriffsschutz über Contao-Mitgliedergruppen (`protected` + `groups`) auf Startpunkt-, Kategorie- und Forenebene
* Themen- und Beitragsliste mit eigener Zeilendarstellung (Autor, Datum, Ansichten, Auszug), Filter-, Such- und Sortierleiste
* Beiträge werden mit TinyMCE erfasst; Feld für Dateianhänge (`fileTree`) vorbereitet

### Offen / nächste Schritte

* Frontend-Modul: Auswahl des Startpunktes, Kategorien-/Foren-Übersicht im Layout von forum.mybb.de, neueste Themen, Statistiken
* Frontend: Themen erstellen und beantworten, Ansichtszähler, Beitragszähler je Mitglied, Avatare (Lucide)
* Vererbung der Mitgliedergruppen zur Laufzeit auswerten (Backend speichert sie bereits pro Ebene)
* Schnellschalter („toggle") für die Veröffentlichung in der Übersicht
* `paste_button_callback`, damit beim Verschieben per Zwischenablage keine ungültigen Verschachtelungen entstehen
* Unit-Tests unter `tests/`
