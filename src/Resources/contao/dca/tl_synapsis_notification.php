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
 * Tabelle tl_synapsis_notification
 *
 * Persoenliche Benachrichtigung eines Mitglieds im Benachrichtigungscenter
 * ("Mein Bereich"). Ereignistypen: reply (Antwort auf mein Thema), quote
 * (mein Beitrag zitiert), mention (@Erwaehnung) und report (neue Meldung, an
 * die Moderation). Reine Frontend-Pflege; die DCA dient allein der
 * Tabellenerzeugung durch contao:migrate.
 */
$GLOBALS['TL_DCA']['tl_synapsis_notification'] = array
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
                'id'              => 'primary',
                'member,isRead'   => 'index',
                'post'            => 'index',
                'topic'           => 'index',
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
        'fromMember' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'type' => array
        (
            'sql' => "varchar(16) NOT NULL default ''",
        ),
        'topic' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'post' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'forum' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'isRead' => array
        (
            'sql' => "char(1) NOT NULL default ''",
        ),
    ),
);
