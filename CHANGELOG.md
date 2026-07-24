# Synapsis Changelog

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
