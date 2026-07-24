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
 * Tabelle tl_synapsis_read
 *
 * Merkt sich fuer jedes Mitglied und Thema den Zeitpunkt des zuletzt gelesenen
 * Beitrags (lastRead). Ein Thema gilt als ungelesen, wenn sein neuester Beitrag
 * juenger als lastRead ist (oder noch kein Eintrag existiert). Reine
 * Verknuepfungstabelle, nur programmatisch aus dem Frontend gepflegt; die DCA
 * dient allein der Tabellenerzeugung durch contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_read'] = array
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
                'member,topic' => 'unique',
                'topic'        => 'index',
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
        // Zeitpunkt des zuletzt gelesenen Beitrags in diesem Thema
        'lastRead' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
    ),
);
