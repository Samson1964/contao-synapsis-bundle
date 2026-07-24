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
 * Tabelle tl_synapsis_poll
 *
 * Optionale Umfrage zu einem Thema (pid = tl_synapsis_topic.id, hoechstens eine
 * je Thema). "multiple" unterscheidet Single Choice ('') von Multiple Choice
 * ('1'). Die Antwortmoeglichkeiten liegen in tl_synapsis_poll_option, die
 * Stimmen in tl_synapsis_poll_vote. Reine Frontend-Pflege; die DCA dient allein
 * der Tabellenerzeugung durch contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_poll'] = array
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
        'question' => array
        (
            'sql' => "varchar(255) NOT NULL default ''",
        ),
        // '' = Single Choice, '1' = Multiple Choice
        'multiple' => array
        (
            'sql' => "char(1) NOT NULL default ''",
        ),
        // Zeitpunkt, ab dem nicht mehr abgestimmt werden kann (Umfrageende)
        'closeDate' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        // '1' = Ergebnisse erst nach Umfrageende sichtbar
        'hideResults' => array
        (
            'sql' => "char(1) NOT NULL default ''",
        ),
    ),
);
