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
 * Tabelle tl_synapsis_poll_vote
 *
 * Eine Stimme: welches Mitglied welche Antwortmoeglichkeit (option) einer
 * Umfrage (poll) gewaehlt hat. Bei Single Choice gibt es je Mitglied genau eine
 * Zeile, bei Multiple Choice eine je gewaehlter Option. Die Eindeutigkeit
 * (member,option) verhindert Doppelstimmen auf dieselbe Option. Reine
 * Frontend-Pflege; die DCA dient allein der Tabellenerzeugung.
 */
$GLOBALS['TL_DCA']['tl_synapsis_poll_vote'] = array
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
                'id'            => 'primary',
                'member,choice' => 'unique',
                'poll'          => 'index',
                'choice'        => 'index',
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
        'poll' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        // gewaehlte Antwortmoeglichkeit (Spaltenname "choice" statt "option",
        // da "option" ein reserviertes SQL-Wort ist)
        'choice' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'member' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
    ),
);
