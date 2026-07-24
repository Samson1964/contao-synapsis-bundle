<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Erweiterung der Tabelle tl_module um den Modultyp "synapsis_forum".
 *
 * Das Modul zeigt die Forenstruktur eines Startpunkts im Frontend an. Die
 * Palette und die zusaetzlichen Felder werden an die bestehende tl_module-DCA
 * angehaengt (die Kern-DCA wird von Contao geladen).
 */

// Palette des Modultyps
$GLOBALS['TL_DCA']['tl_module']['palettes']['synapsis_forum'] = '{title_legend},name,headline,type;'
    .'{config_legend},synapsis_root,synapsis_perPage,synapsis_editor;'
    .'{upload_legend},synapsis_allowUploads;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID';

// Unterpalette: Upload-Ordner nur bei aktivierten Uploads
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['synapsis_allowUploads'] = 'synapsis_uploadFolder';

// __selector__ um das Upload-Feld erweitern
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'synapsis_allowUploads';

// Felder
$GLOBALS['TL_DCA']['tl_module']['fields']['synapsis_root'] = array
(
    'exclude'   => true,
    'inputType' => 'select',
    // options_callback: ModuleListener::getRootOptions (services.yaml)
    'eval'      => array('mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'),
    'sql'       => "int(10) unsigned NOT NULL default 0",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['synapsis_perPage'] = array
(
    'exclude'   => true,
    'inputType' => 'text',
    'default'   => 20,
    'eval'      => array('rgxp' => 'natural', 'maxlength' => 5, 'tl_class' => 'w50'),
    'sql'       => "smallint(5) unsigned NOT NULL default 20",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['synapsis_editor'] = array
(
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50 m12'),
    'sql'       => "char(1) NOT NULL default '1'",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['synapsis_allowUploads'] = array
(
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr'),
    'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['synapsis_uploadFolder'] = array
(
    'exclude'   => true,
    'inputType' => 'fileTree',
    'eval'      => array('fieldType' => 'radio', 'files' => false, 'mandatory' => true, 'tl_class' => 'clr'),
    'sql'       => "binary(16) NULL",
);
