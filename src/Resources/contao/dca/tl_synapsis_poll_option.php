<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_Table;
use Schachbulle\ContaoSynapsisBundle\SchachbulleContaoSynapsisBundle;

/*
 * Tabelle tl_synapsis_poll_option
 *
 * Antwortmoeglichkeit einer Umfrage (pid = tl_synapsis_poll.id). Reine
 * Frontend-Pflege; die DCA dient allein der Tabellenerzeugung durch
 * contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_poll_option'] = array
(
    'config' => array
    (
        'dataContainer' => SchachbulleContaoSynapsisBundle::isContao5() ? DC_Table::class : 'Table',
        'notEditable'   => true,
        'closed'        => true,
        'sql' => array
        (
            'keys' => array
            (
                'id'  => 'primary',
                'pid' => 'index',
            ),
        ),
    ),

    'fields' => array
    (
        'id' => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ),
        'pid' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'sorting' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'label' => array
        (
            'sql' => "varchar(255) NOT NULL default ''",
        ),
    ),
);
