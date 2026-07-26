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
 * Tabelle tl_synapsis_online
 *
 * Kurzlebige Praesenz-Daten fuer "Wer ist online": je Besuchersitzung eine Zeile
 * mit dem Zeitstempel des letzten Forenaufrufs. member = 0 fuer Gaeste. Alte
 * Zeilen werden beim Aufraeumen entfernt. Reine Laufzeitpflege; die DCA dient
 * allein der Tabellenerzeugung durch contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_online'] = array
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
                'sessionId' => 'primary',
                'tstamp'    => 'index',
            ),
        ),
    ),

    'fields' => array
    (
        'sessionId' => array
        (
            'sql' => "varchar(64) NOT NULL default ''",
        ),
        'member' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
    ),
);
