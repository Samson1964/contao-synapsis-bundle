<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Bezeichnungen des Backend-Bereichs "Synapsis" und seiner beiden Module.
 *
 * "synapsis" ist die Gruppe (String), "synapsis_forum" und "synapsis_csv" sind
 * die Module (Array aus Label und Beschreibung).
 */
$GLOBALS['TL_LANG']['MOD']['synapsis'] = 'Synapsis-Forum';
$GLOBALS['TL_LANG']['MOD']['synapsis_forum'] = array('Forum', 'Die Forenstruktur mit Kategorien, Foren, Themen und Beiträgen verwalten.');
$GLOBALS['TL_LANG']['MOD']['synapsis_csv'] = array('Import', 'Ein Forum aus einem Fremdsystem importieren (derzeit phpBB-CSV-Export). Foren auswählbar; Verfasser werden als Gast übernommen.');
$GLOBALS['TL_LANG']['MOD']['synapsis_settings'] = array('Einstellungen', 'Globale Foreneinstellungen wie die E-Mail-Vorlagen für neue Antworten.');
