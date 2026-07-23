# Synapsis Changelog

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
