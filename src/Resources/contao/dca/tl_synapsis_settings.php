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
        'default' => '{design_legend},colorScheme;{community_legend},showOnline,showRanks,ranks;{notify_legend},notifyEnabled,notifySubject,notifyBody;{team_legend},teamNotifyAdmins,teamNotifyMods,teamNotifyOn,teamSubject,teamBody;{sender_legend},senderName,senderEmail;{moderators_legend},modCanPin,modCanLock,modCanMove,modCanEditPosts',
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
        // Farbschema des Frontends (leer = Standard). Weitere Schemata sind als
        // CSS-Klassen in synapsis.css hinterlegt (synapsis-scheme--<wert>).
        'colorScheme' => array
        (
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => array('', 'petrol', 'gold', 'rot', 'orange'),
            'reference' => &$GLOBALS['TL_LANG']['tl_synapsis_settings']['colorSchemeRef'],
            // wizard-Callback: SettingsListener::colorSchemeWizard (Farbvorschau-Raster)
            'eval'      => array('tl_class' => 'clr'),
            'sql'       => "varchar(32) NOT NULL default ''",
        ),
        // --- Community & Mitglieder ---
        'showOnline' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'",
        ),
        'showRanks' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'",
        ),
        'ranks' => array
        (
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => array('rows' => 5, 'tl_class' => 'clr', 'decodeEntities' => true),
            'sql'       => "text NULL",
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
        // --- Rechte der Moderatoren (Administratoren duerfen immer alles) ---
        'modCanPin' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'",
        ),
        'modCanLock' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'",
        ),
        'modCanMove' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'",
        ),
        'modCanEditPosts' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'",
        ),

        // --- Benachrichtigung an das Team (Admins/Moderatoren) ---
        'teamNotifyAdmins' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        'teamNotifyMods' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        'teamNotifyOn' => array
        (
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => array('topic', 'reply', 'both'),
            'reference' => &$GLOBALS['TL_LANG']['tl_synapsis_settings']['teamNotifyOnRef'],
            'eval'      => array('tl_class' => 'w50'),
            'sql'       => "varchar(8) NOT NULL default 'both'",
        ),
        'teamSubject' => array
        (
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('maxlength' => 255, 'tl_class' => 'clr long', 'decodeEntities' => true),
            'sql'       => "varchar(255) NOT NULL default ''",
        ),
        'teamBody' => array
        (
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => array('rows' => 6, 'tl_class' => 'clr', 'decodeEntities' => true),
            'sql'       => "text NULL",
        ),
    ),
);
