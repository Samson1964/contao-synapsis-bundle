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
 * Tabelle tl_synapsis_report
 *
 * Meldung eines Beitrags durch ein Mitglied. Moderatoren/Administratoren des
 * betroffenen Forums sehen offene Meldungen im Frontend und koennen sie als
 * erledigt markieren (loeschen). Reine Frontend-Pflege; die DCA dient allein
 * der Tabellenerzeugung durch contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_report'] = array
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
                'id'           => 'primary',
                'post'         => 'index',
                'forum'        => 'index',
                'member,post'  => 'unique',
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
        'post' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'topic' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'forum' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'member' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'reason' => array
        (
            'sql' => "text NULL",
        ),
    ),
);
