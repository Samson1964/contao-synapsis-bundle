<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Deutsche Sprachdatei der tl_module-Erweiterung (Modultyp synapsis_forum).
 */

// Modultyp im Auswahlmenue
$GLOBALS['TL_LANG']['FMD']['synapsis'] = 'Synapsis-Forum';
$GLOBALS['TL_LANG']['FMD']['synapsis_forum'] = array('Forum', 'Zeigt die Forenstruktur eines Startpunkts im Frontend an.');

// Felder
$GLOBALS['TL_LANG']['tl_module']['synapsis_root']         = array('Startpunkt', 'Wählen Sie die Forenstruktur (den Startpunkt) aus, die dieses Modul anzeigt.');
$GLOBALS['TL_LANG']['tl_module']['synapsis_perPage']      = array('Einträge pro Seite', 'Anzahl der Themen bzw. Beiträge pro Seite (Seitennummerierung).');
$GLOBALS['TL_LANG']['tl_module']['synapsis_editor']       = array('TinyMCE-Editor verwenden', 'Beiträge mit dem TinyMCE-Editor (inkl. Emoticons) erfassen.');
$GLOBALS['TL_LANG']['tl_module']['synapsis_allowUploads'] = array('Dateianhänge erlauben', 'Mitgliedern erlauben, Dateien an Beiträge anzuhängen.');
$GLOBALS['TL_LANG']['tl_module']['synapsis_uploadFolder'] = array('Upload-Verzeichnis', 'Verzeichnis, in dem hochgeladene Dateianhänge gespeichert werden.');

// Legenden
$GLOBALS['TL_LANG']['tl_module']['config_legend'] = 'Forum-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['upload_legend'] = 'Dateianhänge';
