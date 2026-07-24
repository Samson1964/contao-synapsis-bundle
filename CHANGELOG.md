# Synapsis Changelog

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
