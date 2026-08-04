# Synapsis Changelog

## Version 1.17.2 (2026-07-28)

> **Sicherheits-Update – bitte zeitnah einspielen.** Keine Schema-Änderung; nach dem Update
> `composer update` und den Cache leeren.

### Behoben (Sicherheit)

* **Geschützter Startpunkt war für Gäste einsehbar**: Zwei Lücken ließen Inhalte eines per
  Zugriffsschutz auf Mitgliedergruppen beschränkten Startpunkts nach außen:
  1. Die Gäste-Checkbox eines **untergeordneten** Knotens hob den Schutz des gesamten
     Bereichs auf. Besonders betroffen: **importierte Foren**, die standardmäßig „Gäste
     dürfen lesen" tragen – sie machten den geschützten Startpunkt samt Inhalten öffentlich.
     Jetzt gilt: Ein geschützter Knoten ohne **eigene** Gäste-Freigabe (Gäste-Gruppe oder
     eigene Checkbox) blockiert den öffentlichen Zugriff für seinen gesamten Teilbereich –
     Checkboxen anderer Knoten können das nicht aufheben. Das schließt auch die umgekehrte
     Richtung: ein selbst geschütztes Forum bleibt geschützt, auch wenn eine übergeordnete
     Kategorie öffentlich lesbar ist.
  2. Die **Kategorie-Einzelansicht** (`?category=<id>`) prüfte den Lesezugriff nicht und
     zeigte Gästen Titel und Foren eines geschützten Bereichs.
* Abgesichert durch einen neuen Sieben-Wege-Lecktest (Übersicht, Forum, Thema, Kategorie,
  Suche, beide Feeds – jeweils als Gast) samt Import-Szenario sowie vier neue/geschärfte
  Unit-Tests der Zugriffslogik (jetzt 92 Tests).

## Version 1.17.1 (2026-07-28)

> Keine Schema-Änderung, reine Korrektur – nach dem Update genügt `composer update`.

### Behoben

* **Dokumentations-Ordner**: Version 1.17.0 hatte die Umbenennung des Handbuch-Ordners von
  `doc/` nach `docs/` (Repo-Konvention) versehentlich rückgängig gemacht – die Links in der
  README liefen dadurch ins Leere. Der Ordner heißt wieder `docs/`.

## Version 1.17.0 (2026-07-28)

> Keine Schema-Änderung. Da sich die Service-Registrierung geändert hat, nach dem Update den
> **Anwendungscache neu bauen** (`cache:clear`, bei OPcache PHP neu starten).

### Hinzugefügt

* **Backend: „Nur diesen Knoten anzeigen"** – Ein Klick auf den **Namen** eines Startpunkts,
  einer Kategorie oder eines Forums grenzt die Baumansicht auf diesen Knoten ein (wie in der
  Contao-Seitenstruktur). Über dem Baum erscheint ein **Navigationspfad** („Alle" › Startpunkt
  › … › Knoten) zum Zurückspringen; die Auswahl bleibt in der Sitzung gemerkt.
* **Zitat-Knopf im Editor**: Die TinyMCE-Werkzeugleiste enthält jetzt **„Blockquote"** – der
  gerade ausgewählte Text wird als Zitatblock formatiert (gleiche Darstellung wie beim
  „Zitieren" eines Beitrags).

### Geändert

* **„Neueste Themen" → „Neueste Beiträge"**: Die Liste auf der Übersicht zeigt jetzt
  **Verfasser und Datum des jeweils neuesten Beitrags** (statt des Themenerstellers) und ist
  danach sortiert. Der Link führt **direkt zum neuesten Beitrag** – inklusive der richtigen
  Seite der Beitragsliste und Sprungmarke.

## Version 1.16.0 (2026-07-28)

> Keine Schema-Änderung. Da sich die Service-Registrierung geändert hat, nach dem Update den
> **Anwendungscache neu bauen** (`cache:clear`, bei OPcache PHP neu starten) und die **Assets neu
> veröffentlichen** (CSS geändert).

### Verbessert

* **Mitglieder-Auswahl skaliert jetzt**: Die Felder **„Administratoren: Mitglieder"**,
  **„Moderatoren: Mitglieder"** und **„Umfragen: erlaubte Mitglieder"** luden bisher **alle**
  Mitglieder in eine Auswahlliste – bei zehntausenden Mitgliedern fror das Backend ein. Die
  Felder nutzen jetzt den **Contao-Auswahldialog** (Picker): Ein Klick öffnet die
  Mitgliederliste des Backends mit **Suche und Blättern**; gespeichert wird unverändert die
  ID-Liste – **bestehende Zuordnungen bleiben erhalten**, keine Migration nötig.

### Behoben

* **Bild-Anhänge**: Die Vorschau passt sich jetzt der **Breite des Beitrags** an (vorher fest
  160 px), und ein Klick öffnet zuverlässig die **Lightbox** statt eines neuen Fensters – das
  `target="_blank"` am Bild-Link entfiel, und die Lightbox-/Permalink-Skripte binden sich
  robust dokumentweit (unabhängig davon, ob das Website-Theme Skripte verschiebt oder bündelt).

### Intern

* Das lokale `vendor/`-Verzeichnis gehört nicht mehr zum Arbeitsordner des Repos; die
  Unit-Tests laufen über eine zentrale PHPUnit-Installation (für Mitentwickler unverändert:
  `composer install` → `vendor/bin/phpunit`).

## Version 1.15.0 (2026-07-26)

> Keine Schema-Änderung – nach dem Update `composer update`, Cache leeren und die **Assets neu
> veröffentlichen** (CSS geändert).

### Hinzugefügt

* **Farbschema „Grün"**: Sechstes Schema (`synapsis-scheme--gruen`), farblich an das
  MERCONIS-Forum von leadingsystems.de angelehnt (Akzent `#77ab40`). Wählbar über die
  Farbvorschau in den Einstellungen.

### Behoben

* **Listen in Beiträgen**: Nummerierte und Aufzählungslisten (`ol`/`ul`) aus dem Editor wurden
  vom CSS mancher Websites „geschluckt" (keine Punkte/Zahlen, keine Einrückung). Das Bundle
  erzwingt jetzt in Beitragstexten die üblichen Aufzählungszeichen samt Einrückung und
  neutralisiert Theme-eigene `li::before`-Symbole.
* **Themenansicht-Kopf**: Der Thementitel (h2) steht jetzt **über** der Moderations-/
  Aktionsleiste statt daneben; die Leiste bricht bei vielen Schaltflächen sauber um.
* **Auswahlfelder lesbar**: „Verschieben nach …" (Themenansicht) und die Auswahlfelder der
  Massen-Moderation werden jetzt defensiv gegen das Website-CSS gestylt (Rahmen, Hintergrund,
  Schriftgröße) und sind überall gut erkennbar.

### Geprüft (Sicherheit)

* **Rechte der Beitrags-Aktionen** auditiert: Serverseitig dürfen **Gäste nie** bearbeiten,
  löschen oder sperren (auch keine Gastbeiträge), **Mitglieder nur eigene Beiträge** (solange
  das Thema offen ist) und **fremde Beiträge nur Moderatoren** (per Einstellung abschaltbar)
  **und Administratoren** – alle Formular-Handler prüfen das unabhängig von der Anzeige. Neue
  automatisierte Gast-Prüfungen sichern das dauerhaft ab. Sieht ein Konto die Schaltflächen an
  fremden Beiträgen, besitzt es eine Admin-/Moderatorenrolle im betreffenden Baum.

## Version 1.14.2 (2026-07-26)

> Keine Schema-Änderung – nach dem Update genügt `composer update` und ein Leeren des Caches.

### Behoben

* **Massen-Löschung hinterließ verwaiste Beiträge**: Beim Löschen ganzer Themen über die
  Massen-Moderation blieben deren Beiträge (und die zugehörigen „Gefällt mir"-Einträge) in der
  Datenbank zurück und verfälschten u. a. die Statistik. `deleteTopicCompletely` entfernt jetzt
  auch die Beiträge samt Likes. (Die Einzel-Löschung war nicht betroffen.)

### Verbessert

* **Sperr-Abfragen zwischengespeichert**: Der Sperrstatus eines Mitglieds wird je Request nur
  noch einmal aus der Datenbank gelesen (vorher eine Abfrage je Beitrag bzw. Schreibrecht-Prüfung).

### Dokumentation

* **Handbuch neu organisiert**: Die README ist jetzt ein kompakter Überblick (Funktionsumfang,
  Installation, Schnellstart); das ausführliche Handbuch liegt in neun Themen-Dateien im neuen
  Ordner **`doc/`** (Grundlagen, Knoten-Einstellungen, Frontend, Moderation, Benachrichtigungen,
  globale Einstellungen, Import, FAQ, Entwickler).
* **Lücken geschlossen**: Das **Statistik-Modul** ist jetzt dokumentiert, die Einstellung
  „Moderatoren dürfen Mitglieder **sperren**" ergänzt (fehlte in der Rechte-Liste), die
  Editor-Beschreibung an 1.14.1 angepasst (Emojis über die Smiley-Leiste) und eine
  **Update-Anleitung** (Cache → Migration → Assets) samt erweiterter FAQ ergänzt.

## Version 1.14.1 (2026-07-26)

> Keine Schema-Änderung – nach dem Update genügt `composer update` und ein Neuveröffentlichen der Assets.

### Behoben

* **Editor**: Die TinyMCE-Konfiguration lud die Plugins `emoticons` und `autolink`, die im mitgelieferten TinyMCE-Build nicht enthalten sind – das erzeugte im Browser zwei Konsolen-Fehler („Failed to load plugin"). Beide wurden aus der Konfiguration entfernt; für Emojis dient weiterhin die eigene Smiley-Leiste unter dem Editor. Der Editor selbst (Fett/Kursiv, Listen, Link, Bild) ist unverändert.

## Version 1.14.0 (2026-07-26)

> **Schema-Änderung**: nach dem Update den **Anwendungscache neu bauen** und `contao:migrate` ausführen (neue Tabelle `tl_synapsis_ban`, neues Einstellungsfeld `modCanBan`). Anschließend die Assets neu veröffentlichen (CSS).

### Hinzugefügt – Admin (Teil 2, Abschluss)

* **Massen-Moderation**: In der Forenansicht können berechtigte Moderatoren/Administratoren jetzt **mehrere Themen auf einmal** bearbeiten. Über Auswahlkästchen an den Themen und eine Aktionsleiste lassen sich die markierten Themen gemeinsam **schließen/öffnen, verschieben oder löschen**. Jede Aktion wird gegen die passende Berechtigung geprüft (Schließen → „Themen schließen", Verschieben → „Themen verschieben", Löschen → „fremde Beiträge bearbeiten/löschen"); Löschen wird zusätzlich per Sicherheitsabfrage bestätigt. Es werden nur Themen des jeweiligen Forums berücksichtigt.
* **Sperr-/Bann-Verwaltung**: Mitglieder können für das Forum **gesperrt** werden – ein gesperrtes Mitglied kann keine Themen mehr erstellen und nicht mehr antworten (Lesen bleibt möglich). Neuer Bereich **„Sperren"** unter „Mein Bereich" (nur für Berechtigte): Liste der gesperrten Mitglieder mit **Freigeben**, dazu ein Feld zum Sperren über den **Benutzernamen** (mit optionaler Begründung). Zusätzlich eine **„Sperren"-Schaltfläche** direkt an Beiträgen. Neue Einstellung **„Moderatoren dürfen Mitglieder sperren"** (Standard: aus – Administratoren dürfen es immer). Logik in der testbaren Klasse `Frontend\BanManager` (Tabelle `tl_synapsis_ban`).

Damit ist der Bereich **Administration** vollständig.

## Version 1.13.0 (2026-07-26)

> Keine Schema-Änderung und keine `config.php`/`services.yaml`-Änderung – nach dem Update genügt ein **Leeren des Contao-Caches** (bzw. ein OPcache-Reset), kein Neubau des Anwendungscontainers nötig.

### Hinzugefügt – Import aus dem Support-Ticket-System (Fast-Media)

* **Neues Import-Format** im Backend-Modul **„Import"**: Neben dem phpBB-CSV-Import gibt es jetzt „Support-Ticket-System (aktuelle Datenbank)". Es wird **nur angeboten, wenn die Tabellen `tl_support_*` in der Datenbank vorhanden sind**. Kein Datei-Upload – die Daten werden direkt aus der laufenden Datenbank gelesen.
* **Ablauf wie beim phpBB-Import**: Zielkategorie wählen, dann die zu übernehmenden Foren auswählen. Zuordnung:
  * `tl_support_archive` (Typ `forum` **und** `support`) → **Foren** (Teaser wird zur Beschreibung).
  * `tl_support_ticket` → **Themen** (Titel entschlüsselt, `hits` → Aufrufe, `closed` → gesperrt, Datum übernommen).
  * `tl_support_comment` → **Beiträge** (Text ist bereits HTML und bleibt erhalten); der **Eröffnungsbeitrag** ist der älteste Kommentar des Tickets.
* **Verfasser 1:1**: Die Support-Benutzer sind echte Contao-Mitglieder – `member_id` wird direkt als `author` übernommen (kein „Gast (Name)" wie bei phpBB). Der Anzeigename wird wie üblich live aus `tl_member` aufgelöst.
* Leere Tickets (ohne veröffentlichten Kommentar) und unveröffentlichte Kommentare werden übersprungen. `tl_support_category` und `tl_support_notify` bleiben unberücksichtigt; Datei-Anhänge werden nicht übernommen.
* Importierte Foren sind zunächst **öffentlich lesbar** (im Backend anpassbar). Logik in der Klasse `Backend\SupportImporter`; gegen die echten Daten (13 Foren, 139 Themen, 432 Beiträge) auf Contao 4.13 und 5 verifiziert.

## Version 1.12.0 (2026-07-26)

> Enthält eine Schema-Änderung: nach dem Update `contao:migrate` ausführen (neues Feld `wordFilter` in den Einstellungen). Da eine `config.php` (neues Backend-Modul) betroffen ist, den **Anwendungscache neu bauen**; Assets neu veröffentlichen.

### Hinzugefügt – Admin (Teil 1)

* **Wortfilter**: Neue Einstellung „Wortfilter" (Bereich Administration). Konfigurierte Wörter werden in **Beitragstexten und Titeln** ersetzt – je Zeile „Wort" (→ Sternchen) oder „Wort=Ersatz". Verglichen wird als ganzes Wort und ohne Beachtung der Groß-/Kleinschreibung; **HTML/Links bleiben unangetastet** (nur Textteile werden gefiltert). Logik in der testbaren Klasse `Frontend\WordFilter`.
* **Backend-Statistik**: Neues Backend-Modul **„Statistik"** (Gruppe Synapsis) mit einer Lese-Übersicht: Gesamtzahlen (Startpunkte, Kategorien, Foren, Themen, Beiträge, aktive Mitglieder, offene Meldungen), Aufstellung je Startpunkt, aktivste Mitglieder und die letzten Beiträge.

## Version 1.11.0 (2026-07-26)

> Keine Schema-Änderung – nach dem Update genügt `composer update` und ein Neuveröffentlichen der Assets (CSS/JS).

### Hinzugefügt – Inhalt & Bedienung (Teil 2)

* **RSS-Feed je Forum und Startpunkt**: Übersicht und Forenansicht bieten einen **RSS-2.0-Feed** der neuesten Themen (Schaltfläche „RSS" + automatische Feed-Erkennung im Seitenkopf). Aufruf über `?feed=1` bzw. `?forum=<id>&feed=1`; die Ausgabe wird sauber über eine `ResponseException` kurzgeschlossen (Themen-Links im richtigen Seitenkontext).
* **Entwürfe speichern**: Der Text im Antwort- bzw. Neues-Thema-Feld wird **lokal im Browser** (localStorage) zwischengespeichert. Beim erneuten Aufruf erscheint eine Leiste zum **Wiederherstellen** oder **Verwerfen**; beim Absenden wird der Entwurf automatisch verworfen. Funktioniert mit dem TinyMCE-Editor und einer einfachen Textarea – ohne Server und ohne zusätzliche Tabelle.

Damit ist der Bereich **„Inhalt & Bedienung"** vollständig.

## Version 1.10.0 (2026-07-26)

> Keine Schema-Änderung – nach dem Update genügt `composer update` und ein Neuveröffentlichen der Assets (CSS).

### Hinzugefügt – Inhalt & Bedienung

* **Permalink je Beitrag**: Jeder Beitrag hat einen **„Link"** (Direktlink samt Sprungmarke `#post-<id>`); ein Klick kopiert die Adresse in die Zwischenablage (mit Fallback).
* **„Als gelesen markieren"**: In der Forenansicht **„Forum als gelesen markieren"** (Forum samt Unterforen), auf der Übersicht **„Alles als gelesen markieren"** (ganzer Startpunkt) – jeweils für angemeldete Mitglieder (`ReadTracker::markAllRead`).
* **Bild-Lightbox**: Bild-Anhänge öffnen sich per Klick als Vollbild-Overlay (Schließen per Klick oder Esc) – ohne zusätzliche Bibliothek.

## Version 1.9.1 (2026-07-26)

> Keine Schema-Änderung – nach dem Update genügt `composer update`.

### Behoben

* **Abonnements bleiben im Startpunkt**: In „Mein Bereich → Abonnements" wurden auch abonnierte Themen aus **anderen Startpunkten** angezeigt, sofern sie öffentlich lesbar waren (die bisherige Sichtbarkeitsprüfung war nicht startpunkt-gebunden). Die Liste ist jetzt – wie „Meine Beiträge", „Ungelesene" und „Gefällt mir" – auf die Foren des angezeigten Startpunkts begrenzt. (Ein Test über zwei Startpunkte bestätigt alle vier Ansichten.)

## Version 1.9.0 (2026-07-26)

> Enthält Schema-Änderungen: nach dem Update `contao:migrate` ausführen (neue Tabelle `tl_synapsis_online`, neue Felder `showOnline`/`showRanks`/`ranks` in den Einstellungen). Da eine `services.yaml`/DCA betroffen ist, den **Anwendungscache neu bauen**; Assets neu veröffentlichen (CSS).

### Hinzugefügt – Community & Mitglieder

* **Mitgliederprofil**: Ein Klick auf einen Autornamen (in Beiträgen) öffnet dessen Profil – **Avatar, Rangstufe, „Mitglied seit", Beitragszahl** (im Startpunkt), **Signatur** und die **letzten Beiträge**. Aufruf über `?member=<id>`.
* **„Wer ist online"**: Auf der Übersicht werden die aktuell aktiven Mitglieder (letzte 5 Minuten) samt **Gästezahl** angezeigt (Präsenz je Sitzung in `tl_synapsis_online`). Global in den Einstellungen abschaltbar.
* **Rangstufen nach Beitragszahl**: Bei Beiträgen und im Profil erscheint eine Rangstufe. Die Stufen sind konfigurierbar (je Zeile „Mindestbeiträge|Titel"); ohne Konfiguration gelten Standardstufen (Neuling, Mitglied, Stammgast, Erfahren, Veteran). Logik in der testbaren Klasse `Frontend\RankResolver`. In den Einstellungen abschaltbar.

## Version 1.8.0 (2026-07-26)

> Keine Schema-Änderung. Da eine `services.yaml` betroffen ist, nach dem Update den **Anwendungscache neu bauen** (`cache:clear` bzw. Contao Manager; bei OPcache PHP neu starten). Assets neu veröffentlichen (CSS).

### Geändert – Farbschemata

* **Visuelle Auswahl**: Das Farbschema wird jetzt über eine **anklickbare Farbvorschau** gewählt (analog zum Lucide-Icon-Wizard) statt nur über eine Auswahlliste.
* **Neue Schemata**: zusätzlich **Rot** und **Orange** (angelehnt an contao.org) – insgesamt fünf: Standard (Blau), Petrol, Gold, Rot, Orange.
* **Markennamen entfernt**: aus „Schachbund (Petrol)" wird **Petrol**, aus „BdF (Gold)" wird **Gold**. Die CSS-Klassen heißen entsprechend `synapsis-scheme--petrol` bzw. `--gold`.

> **Hinweis:** Wer bisher „Schachbund" oder „BdF" gewählt hatte, wählt bitte einmalig neu **Petrol** bzw. **Gold** – die alten Werte greifen nicht mehr (Anzeige fällt sonst auf Standard zurück).

## Version 1.7.1 (2026-07-26)

> Keine Schema-Änderung. Da eine `services.yaml` betroffen ist, muss nach dem Update der **Anwendungscache neu gebaut** werden (`vendor/bin/contao-console cache:clear` bzw. Contao Manager → Systemwartung; bei aktivem OPcache PHP neu starten).

### Behoben

* **Baumstruktur: keine Einträge unterhalb eines Forums**: Im Backend-Baum ist „Hineinfügen" unter einem **Forum** jetzt **deaktiviert** (ein Forum enthält nur Themen) – „Danach einfügen" (Geschwister) bleibt möglich. Umgesetzt über einen `paste_button_callback`; zusätzlich verhindert ein `oncreate`-Sicherheitsnetz das Anlegen unter einem Forum (mit Hinweismeldung). Im echten Contao-4.13-Backend verifiziert.

## Version 1.7.0 (2026-07-26)

> Keine Schema-Änderung – nach dem Update genügt `composer update` und ein Neuveröffentlichen der Assets (CSS).

### Hinzugefügt / Geändert – Import

* **Foren-Auswahl beim Import**: Der Import ist jetzt zweistufig – erst die CSV-Dateien hochladen, dann **auswählen, welche Foren** in die Kategorie übernommen werden.
* **Ziel-Kategorie mit Startpunkt**: Die Auswahlliste zeigt „**Startpunkt › Kategorie**", damit bei gleichnamigen Kategorien nicht versehentlich in den falschen Startpunkt importiert wird.
* **Modul heißt jetzt „Import"** (statt „phpBB Import") mit einer **Formatauswahl** – so lassen sich später weitere Importquellen ergänzen, ohne einen zusätzlichen Menüpunkt. Der phpBB-Import bleibt unverändert.
* **Umfragen sauberer**: Bei importierten Umfragen wird das benutzerdefinierte phpBB-`<t>`-Tag um Frage und Antworten jetzt entfernt.

### Behoben

* **Button-Angleichung**: „Zitieren", „Melden", „Löschen" und **„Gefällt mir"** sind jetzt einheitliche Pill-Buttons (das Herz-Icon bleibt). Die Regeln sind so abgesichert (Vorfahr + Element + Reset-Eigenschaften), dass sie das CSS der umgebenden Website sicher überschreiben – „Gefällt mir" fällt nicht mehr aus dem Rahmen.

## Version 1.6.0 (2026-07-26)

> Keine Schema-Änderung – nach dem Update genügt `composer update` (kein `contao:migrate` nötig).

### Geändert

* **Import auf phpBB umgestellt**: Das Backend-Modul heißt jetzt **„phpBB Import"** und übernimmt einen **phpBB-CSV-Export** direkt. Hochgeladen werden die phpBB-Tabellen `phpbb_forums`, `phpbb_topics`, `phpbb_posts` (Pflicht) sowie optional `phpbb_users` (Anzeigenamen) und `phpbb_poll_options` (Umfragen). Der Import erfolgt in eine **Kategorie**.
  * Übernommen werden **Foren** (phpBB-Kategorien/Container werden übersprungen), **Themen** (inkl. Aufruf-Zähler, angeheftet/geschlossen), **Beiträge** und **Umfragen** (samt Ergebnis).
  * Der phpBB-**Beitragstext** (Legacy-BBCode mit `bbcode_uid` **oder** phpBB-3.x-XML) wird nach sauberem, sicherem **HTML** gewandelt: Fett/Kursiv/Unterstrichen, Links, Bilder, Zitate (auch verschachtelt), Listen, Code, Farbe, Zeilenumbrüche; Smilies bleiben als Text erhalten.
  * **Verfasser** werden als **Gast** mit ihrem phpBB-Namen abgelegt (Anzeige „Gast (Name)"); private Nachrichten und Datei-Anhänge werden nicht übernommen.
  * Importierte Foren sind zunächst **öffentlich lesbar** (im Backend anpassbar).
  * Neue Klassen `Backend\PhpbbImporter` und die testbare `Backend\PhpbbTextConverter` (16 Unit-Tests); die alte generische `CsvIo` entfällt. Weitere Importformate lassen sich über die Formatauswahl ergänzen.

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
