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
 * Tabelle tl_synapsis_settings
 *
 * Globale Foreneinstellungen als einzelner Datensatz (id=1). Das Backend-Modul
 * "Einstellungen" springt direkt in den Bearbeiten-Dialog dieses Satzes (siehe
 * SettingsListener::onLoad). Aktuell: E-Mail-Vorlagen fuer die Benachrichtigung
 * bei neuen Antworten sowie ein optionaler Absender.
 *
 * In den Vorlagen stehen folgende Platzhalter zur Verfuegung:
 *   ##topic##  Titel des Themas
 *   ##name##   Name des Empfaengers
 *   ##url##    absolute Adresse des Themas
 */
$GLOBALS['TL_DCA']['tl_synapsis_settings'] = array
(
    'config' => array
    (
        'dataContainer' => SchachbulleContaoSynapsisBundle::isContao5() ? DC_Table::class : 'Table',
        'closed'        => true,
        'notCreatable'  => true,
        'notDeletable'  => true,
        // onload_callback: SettingsListener::onLoad (services.yaml) - legt den
        // Satz an und leitet von der Liste direkt in die Bearbeitung um.
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
            ),
        ),
    ),

    'palettes' => array
    (
        'default' => '{notify_legend},notifyEnabled,notifySubject,notifyBody;{sender_legend},senderName,senderEmail',
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
        'notifyEnabled' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'",
        ),
        'notifySubject' => array
        (
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('maxlength' => 255, 'tl_class' => 'clr long', 'decodeEntities' => true),
            'sql'       => "varchar(255) NOT NULL default ''",
        ),
        'notifyBody' => array
        (
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => array('rows' => 8, 'tl_class' => 'clr', 'decodeEntities' => true),
            'sql'       => "text NULL",
        ),
        'senderName' => array
        (
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('maxlength' => 128, 'tl_class' => 'w50'),
            'sql'       => "varchar(128) NOT NULL default ''",
        ),
        'senderEmail' => array
        (
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''",
        ),
    ),
);
