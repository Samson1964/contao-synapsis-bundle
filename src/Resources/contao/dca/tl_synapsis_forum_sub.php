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
 * Tabelle tl_synapsis_forum_sub
 *
 * Abonnement eines ganzen Forums durch ein Mitglied. Wird ein neues Thema in
 * diesem Forum erstellt, erhalten die Abonnenten eine E-Mail. Reine
 * Frontend-Pflege; die DCA dient allein der Tabellenerzeugung durch
 * contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_forum_sub'] = array
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
                'id'             => 'primary',
                'forum'          => 'index',
                'member,forum'   => 'unique',
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
        'forum' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
    ),
);
