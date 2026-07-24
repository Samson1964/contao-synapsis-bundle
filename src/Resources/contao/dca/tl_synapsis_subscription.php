<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Tabelle tl_synapsis_subscription
 *
 * Reine Verknuepfungstabelle: welches Mitglied welches Thema abonniert hat. Sie
 * wird nur programmatisch aus dem Frontend gepflegt und hat daher kein eigenes
 * Backend-Modul; die DCA dient allein der Tabellenerzeugung durch contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_subscription'] = array
(
    'config' => array
    (
        'dataContainer' => method_exists(DataContainer::class, 'getDriverForTable') ? DC_Table::class : 'Table',
        'notEditable'   => true,
        'closed'        => true,
        'sql' => array
        (
            'keys' => array
            (
                'id'             => 'primary',
                'member,topic'   => 'unique',
                'topic'          => 'index',
            ),
        ),
    ),

    'fields' => array
    (
        'id' => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'member' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'topic' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
    ),
);
