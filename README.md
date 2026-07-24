# Synapsis – Forum für Contao

Synapsis ist ein Forum als Erweiterungs-Bundle für **Contao 4.13 und Contao 5**. Die
Benutzerverwaltung läuft vollständig über Contao-Mitglieder, das Frontend orientiert sich
am Layout und der Funktionalität von [forum.mybb.de](https://forum.mybb.de/).

## Installation

Über den Contao Manager das Paket `schachbulle/contao-synapsis-bundle` installieren oder
per Composer:

```bash
composer require schachbulle/contao-synapsis-bundle
```

Anschließend die Datenbank aktualisieren (Contao Manager → Systemwartung, oder
`vendor/bin/contao-console contao:migrate`).

## Aufbau

Die Forenstruktur liegt vollständig in **einer** Baumtabelle (`tl_synapsis_forum`) und wird
im Backend wie die Seitenstruktur verwaltet. Der Typ eines Knotens bestimmt seine Rolle:

| Typ        | Bedeutung                                                             | Erlaubt darunter        |
|------------|-----------------------------------------------------------------------|-------------------------|
| `root`     | Startpunkt – eine eigenständige Forenstruktur (nur auf oberster Ebene) | Kategorien, Foren       |
| `category` | Kategorie – gruppiert Foren, enthält selbst keine Themen               | Foren                   |
| `forum`    | Forum – enthält Themen                                                 | Unterforen              |

Darunter hängen die Inhalte:

* `tl_synapsis_topic` – Themen eines Forums (Autor = Contao-Mitglied)
* `tl_synapsis_post` – Beiträge eines Themas (Text mit TinyMCE, Dateianhänge)

Mehrere Startpunkte können nebeneinander bestehen; das Frontend-Modul wählt einen davon aus.

## Frontend-Modul

Das Modul „Forum" (`synapsis_forum`) zeigt einen Startpunkt im Frontend an. Eine einzige
Modulinstanz stellt über URL-Parameter alle Ansichten dar:

| URL | Ansicht |
|-----|---------|
| (ohne Parameter) | Übersicht: Kategorien mit Foren, neueste Themen, Statistiken |
| `?forum=<id>` | Themenliste eines Forums (seitenweise) |
| `?topic=<id>` | Beiträge eines Themas samt Antwortformular |
| `?forum=<id>&new=1` | Formular für ein neues Thema |

Einstellungen des Moduls: Startpunkt, Einträge pro Seite, TinyMCE-Editor (mit Emoticons),
Dateianhänge samt Upload-Verzeichnis. Themen anlegen und Antworten schreiben ist
angemeldeten Contao-Mitgliedern vorbehalten; der Zugriffsschutz der Struktur wird dabei
vererbt ausgewertet. Jedes Mitglied erhält ein Lucide-Standard-Avatar.

## Zugriffsschutz

Startpunkte, Kategorien und Foren lassen sich über „Zugriff schützen" auf bestimmte
Contao-Mitgliedergruppen einschränken. Der Schutz gilt für den gesamten Teilbaum darunter.

## Entwicklung

```bash
vendor/bin/phpunit          # Tests
vendor/bin/ecs check src    # Code-Style (Contao Coding Standards)
```

Konventionen des Bundles:

* `declare(strict_types=1);` in jeder PHP-Datei, Kommentare und Labels auf Deutsch
* DCA-Callbacks werden über den Service-Tag `contao.callback` registriert, nicht über die
  `config.php` – bewusst als YAML-Tag statt PHP-Attribut, damit PHP 7.4 unterstützt bleibt
* Der DCA-Treiber wird versionsabhängig gesetzt (`DC_Table::class` bzw. `'Table'`), damit
  dieselbe DCA unter Contao 4.13 und Contao 5 funktioniert

## Lizenz

LGPL-3.0-or-later
