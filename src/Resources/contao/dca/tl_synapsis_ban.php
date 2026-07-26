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
 * Tabelle tl_synapsis_ban
 *
 * Fuer das Forum gesperrte Mitglieder ("Bann"). Ein gesperrtes Mitglied kann
 * keine Themen mehr erstellen und nicht mehr antworten (Lesen bleibt moeglich).
 * Die Pflege erfolgt im Frontend durch das Team (Administratoren bzw. berechtigte
 * Moderatoren); die DCA dient allein der Tabellenerzeugung durch contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_ban'] = array
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
                'id'     => 'primary',
                'member' => 'unique',
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
        // Gesperrtes Mitglied (tl_member.id).
        'member' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        // Begruendung (optional, wird dem Team angezeigt).
        'reason' => array
        (
            'sql' => "text NULL",
        ),
        // Wer hat gesperrt (tl_member.id des Team-Mitglieds).
        'bannedBy' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
    ),
);
