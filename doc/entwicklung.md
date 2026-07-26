# Für Entwickler

[← Zurück zur Übersicht](../README.md)

## Kompatibilität

Das Bundle unterstützt **Contao 4.13 LTS und Contao 5** mit **einer** Code-Basis
(PHP `^7.4 || ^8.0`). Die wichtigsten Mechanismen dafür:

* Die Versionsweiche `SchachbulleContaoSynapsisBundle::isContao5()` (über
  `Composer\InstalledVersions`) setzt in den DCA-Dateien den passenden Treiber
  (`DC_Table::class` unter Contao 5, `'Table'` unter 4.13).
* DCA-Callbacks werden über den Service-Tag `contao.callback` in
  `src/Resources/config/services.yaml` registriert – bewusst als YAML-Tag statt PHP-Attribut,
  damit PHP 7.4 unterstützt bleibt.
* SQL-Definitionen stehen in der klassischen String-Form (`"char(1) NOT NULL default ''"`).

## Struktur

```text
src/
├── ContaoManager/Plugin.php       Registrierung im Contao Manager
├── Backend/                       Backend-Module: Import (CsvModule, PhpbbImporter,
│                                  PhpbbTextConverter, SupportImporter), Statistik (StatsModule)
├── EventListener/DataContainer/   DCA-Callbacks (ForumListener, SettingsListener, …)
├── Frontend/                      Wiederverwendbare, framework-unabhängige Fachlogik
├── Modules/SynapsisForum.php      Das Frontend-Modul (alle Ansichten)
└── Resources/
    ├── config/                    services.yaml, config.php (BE-Module)
    ├── contao/dca/                DCA-Definitionen (tl_synapsis_*)
    ├── contao/languages/de/       Sprachdateien
    ├── contao/templates/          .html5-Templates (mod_synapsis_*)
    └── public/                    CSS, TinyMCE, Assets
```

Die wiederverwendbare Fachlogik liegt in `src/Frontend/` in bewusst framework-unabhängigen,
unit-getesteten Klassen (u. a. `ForumAccess`, `PollAccess`, `RoleAccess`, `PollManager`,
`ReadTracker`, `LikeManager`, `BanManager`, `BBCode`, `AuthorLabel`, `AvatarResolver`,
`RankResolver`, `WordFilter`, `NotificationTemplate`).

## Datenbank

Alle Tabellen tragen das Präfix `tl_synapsis_`:

| Tabelle                    | Inhalt                                        |
|----------------------------|-----------------------------------------------|
| `tl_synapsis_forum`        | Baum: Startpunkte, Kategorien, Foren          |
| `tl_synapsis_topic`        | Themen (pid → Forum)                          |
| `tl_synapsis_post`         | Beiträge (pid → Thema)                        |
| `tl_synapsis_subscription` | Themen-Abonnements                            |
| `tl_synapsis_forum_sub`    | Forum-Abonnements                             |
| `tl_synapsis_read`         | Lesestände                                    |
| `tl_synapsis_like`         | „Gefällt mir"-Markierungen                    |
| `tl_synapsis_report`       | Beitragsmeldungen                             |
| `tl_synapsis_notification` | Benachrichtigungscenter                       |
| `tl_synapsis_poll`/`_option`/`_vote` | Umfragen                            |
| `tl_synapsis_online`       | „Wer ist online"                              |
| `tl_synapsis_ban`          | Mitglieder-Sperren                            |
| `tl_synapsis_settings`     | Globale Einstellungen (Einzelsatz id=1)       |

## Tests und Qualität

```bash
vendor/bin/phpunit          # Unit-Tests (tests/)
vendor/bin/ecs check src    # Code-Style (Contao Coding Standards)
```

Konventionen:

* `declare(strict_types=1);` in jeder PHP-Datei.
* Kommentare, Dokumentation und DCA-Labels auf **Deutsch**.
* Für neue Fachlogik nach Möglichkeit eine pure, unit-testbare Klasse in `src/Frontend/`
  bzw. `src/Backend/` anlegen; datenbanknahe Klassen erhalten die DBAL-`Connection` per
  Konstruktor.

## Versionen

Releases werden als Git-Tags **ohne** `v`-Präfix veröffentlicht (z. B. `1.14.0`). Was sich je
Version geändert hat – und welche Update-Schritte nötig sind – steht im
[CHANGELOG](../CHANGELOG.md); die generelle Update-Reihenfolge in den
[häufigen Fragen](faq.md#nach-einem-update-richtig-aktualisieren).
