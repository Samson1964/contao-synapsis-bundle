<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Erweiterung von tl_member um eine Forensignatur.
 *
 * Die Signatur wird vom Mitglied selbst im Frontend gepflegt (Panel "Signatur")
 * und unter seinen Beitraegen angezeigt. Hier wird nur die Spalte definiert,
 * damit contao:migrate sie anlegt.
 */
$GLOBALS['TL_DCA']['tl_member']['fields']['signature'] = array
(
    'label'     => array('Forensignatur', 'Wird unter den Forenbeiträgen angezeigt.'),
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => array('maxlength' => 500, 'tl_class' => 'clr'),
    'sql'       => "varchar(512) NOT NULL default ''",
);
