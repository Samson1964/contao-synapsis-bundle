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
 * Tabelle tl_synapsis_topic
 *
 * Themen eines Forums. Sie haengen ueber pid/ptable an tl_synapsis_forum und
 * werden in der Elternansicht (Sortier-Modus 4) dargestellt - angeheftete
 * Themen zuerst, danach nach Datum absteigend. Die Beitraege eines Themas
 * liegen in der Kindtabelle tl_synapsis_post.
 *
 * Autor ist immer ein Contao-Mitglied (tl_member), da Themen im Frontend
 * erstellt werden.
 */
$GLOBALS['TL_DCA']['tl_synapsis_topic'] = array
(
    'config' => array
    (
        'dataContainer'    => SchachbulleContaoSynapsisBundle::isContao5() ? DC_Table::class : 'Table',
        'ptable'           => 'tl_synapsis_forum',
        'ctable'           => array('tl_synapsis_post'),
        'enableVersioning' => true,
        'markAsCopy'       => 'title',
        'sql' => array
        (
            'keys' => array
            (
                'id'     => 'primary',
                'pid'    => 'index',
                'alias'  => 'index',
                'author' => 'index',
            ),
        ),
    ),

    'list' => array
    (
        'sorting' => array
        (
            'mode'              => 4, // Elternansicht (Themen eines Forums)
            'fields'            => array('sticky DESC', 'date DESC'),
            'headerFields'      => array('title', 'type', 'description', 'published'),
            'panelLayout'       => 'filter;search,limit',
            'child_record_class' => 'no_padding',
            // child_record_callback: TopicListener::renderTopic (services.yaml)
        ),
        'label' => array
        (
            'fields' => array('title'),
            'format' => '%s',
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ),
        ),
        'operations' => array
        (
            'edit' => array
            (
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ),
            'posts' => array
            (
                // Wechselt in die Beitraege des Themas
                'href' => 'table=tl_synapsis_post',
                'icon' => 'article.svg',
            ),
            'copy' => array
            (
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ),
            'delete' => array
            (
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"',
            ),
            'show' => array
            (
                'href' => 'act=show',
                'icon' => 'show.svg',
            ),
        ),
    ),

    'palettes' => array
    (
        'default' => '{title_legend},title,alias;{meta_legend},author,authorName,date;{config_legend},sticky,locked;{publish_legend},published',
    ),

    'fields' => array
    (
        'id' => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ),
        'pid' => array
        (
            'foreignKey' => 'tl_synapsis_forum.title',
            'sql'        => "int(10) unsigned NOT NULL default 0",
            'relation'   => array('type' => 'belongsTo', 'load' => 'lazy'),
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'title' => array
        (
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''",
        ),
        'alias' => array
        (
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            // save_callback: TopicListener::generateAlias (services.yaml)
            'eval'      => array('rgxp' => 'alias', 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) BINARY NOT NULL default ''",
        ),
        'author' => array
        (
            'exclude'    => true,
            'filter'     => true,
            'search'     => true,
            'inputType'  => 'select',
            'foreignKey' => 'tl_member.username',
            'eval'       => array('mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'),
            'sql'        => "int(10) unsigned NOT NULL default 0",
            'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
        ),
        // Benutzername zum Zeitpunkt des Schreibens (Momentaufnahme). Wird als
        // Autor angezeigt, wenn das Konto (author) nicht mehr existiert:
        // „Gast (frueherer Benutzername)". Bei Importen aus Fremdsystemen ist
        // author=0 und hier steht der urspruengliche Name.
        'authorName' => array
        (
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''",
        ),
        'date' => array
        (
            'default'   => time(),
            'exclude'   => true,
            'sorting'   => true,
            'flag'      => 8,
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'datim', 'datepicker' => true, 'mandatory' => true, 'tl_class' => 'w50 wizard'),
            'sql'       => "int(10) unsigned NULL",
        ),
        'sticky' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        'locked' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        // Ansichtszaehler; wird im Frontend hochgezaehlt und nur angezeigt
        'views' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'published' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('doNotCopy' => true, 'tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
    ),
);
